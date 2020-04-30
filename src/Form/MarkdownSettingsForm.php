<?php

namespace Drupal\markdown\Form;

use Drupal\Component\Serialization\Json;
use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\SubformState;
use Drupal\Core\Form\SubformStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Plugin\PluginDependencyTrait;
use Drupal\Core\Render\Element;
use Drupal\Core\Render\Markup;
use Drupal\Core\Url;
use Drupal\markdown\Config\MarkdownSettings;
use Drupal\markdown\MarkdownParserPluginManagerInterface;
use Drupal\markdown\MarkdownSettingsInterface;
use Drupal\markdown\Plugin\Markdown\ExtensibleMarkdownParserInterface;
use Drupal\markdown\Plugin\Markdown\MarkdownPluginSettingsInterface;
use Drupal\markdown\Traits\MarkdownExtensionPluginManagerTrait;
use Drupal\markdown\Traits\MarkdownParserPluginManagerTrait;
use Drupal\markdown\Traits\MarkdownStatesTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

class MarkdownSettingsForm extends FormBase {

  use MarkdownExtensionPluginManagerTrait;
  use MarkdownParserPluginManagerTrait;
  use MarkdownStatesTrait;
  use PluginDependencyTrait;

  /**
   * The Markdown Settings.
   *
   * @var \Drupal\markdown\Config\MarkdownSettings
   */
  protected $settings;

  /**
   * The default parser.
   *
   * @var string
   */
  protected $parserId;

  /**
   * The Markdown Parser Plugin Manager service.
   *
   * @var \Drupal\markdown\MarkdownParserPluginManagerInterface
   */
  protected $parserManager;

  /**
   * The default parser configuration.
   *
   * @var array
   */
  protected $parserConfiguration;

  /**
   * {@inheritdoc}
   */
  public function __construct(MarkdownParserPluginManagerInterface $parserManager, MarkdownSettingsInterface $settings) {
    $this->parserManager = $parserManager;
    $this->settings = $settings;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container = NULL) {
    if (!$container) {
      $container = \Drupal::getContainer();
    }
    return new static(
      $container->get('plugin.manager.markdown.parser'),
      MarkdownSettings::load('markdown.settings', NULL, $container)->setKeyPrefix('parser')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'markdown_configuration';
  }

  /**
   * Retrieves the parser plugin identifier.
   *
   * @return string
   */
  public function getParserId() {
    if (!isset($this->parserId)) {
      $this->parserId = $this->settings->getParserId();
    }
    return $this->parserId;
  }

  /**
   * Retrieves the parser configuration.
   *
   * @return array
   */
  public function getParserConfiguration() {
    if (!isset($this->parserConfiguration)) {
      $this->parserConfiguration = $this->settings->getParserConfiguration();
    }
    return $this->parserConfiguration;
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['markdown.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form += [
      '#parents' => [],
      '#title' => $this->t('Markdown'),
    ];

    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save configuration'),
      '#button_type' => 'primary',
    ];

    // By default, render the form using system-config-form.html.twig.
    $form['#theme'] = 'system_config_form';

    // Build markdown settings.
    $subform = ['#parents' => []];
    $form['markdown'] = $this->buildSettings($subform, SubformState::createForSubform($subform, $form, $form_state));

    return $form;
  }

  public function buildSettings(array $element, SubformStateInterface $form_state) {
    // Immediately return if there are no installed parsers.
    if (!($labels = $this->parserManager->labels())) {
      $element['missing'] = [
        '#type' => 'item',
        '#title' => $this->t('No markdown parsers installed.'),
        '#description' => $this->t('Visit the <a href=":system.status" target="_blank">@system.status</a> page for more details.', [
          '@system.status' => $this->t('Status report'),
          ':system.status' => Url::fromRoute('system.status', [], ['fragment' => 'markdown'])->toString(),
        ]),
      ];
      $element['actions']['#access'] = FALSE;
      return $element;
    }

    // Include a "Site-wide parser" option if not on the global settings page.
    if ($includeSiteWideOption = \Drupal::routeMatch()->getRouteName() !== 'markdown.settings') {
      /** @var \Drupal\markdown\MarkdownInterface $markdown */
      $markdown = \Drupal::service('markdown');
      $labels = array_merge(
        ['' => $this->t('Site-wide parser (@parser)', [
          '@parser' => $markdown->getParser()->getLabel(),
        ])],
        $labels
      );
    }

    // Add the markdown.admin library to update summaries in vertical tabs.
    $element['#attached']['library'][] = 'markdown/markdown.admin';

    $parents = isset($element['#parents']) ? $element['#parents'] : [];
    $parserId = $form_state->getValue(array_merge($parents, ['parser', 'id']), $this->getParserId());
    $configuration = NestedArray::mergeDeep($this->getParserConfiguration(), $form_state->getValue('parser', []));
    $id = Html::getUniqueId('markdown-parser-ajax');

    $element['ajax'] = [
      '#type' => 'container',
      '#id' => $id,
      '#attributes' => [
        'data-markdown-element' => 'wrapper',
      ],
    ];

    // Build a wrapper for the ajax response.
    $element['ajax']['vertical_tabs'] = [
      '#type' => 'vertical_tabs',
      '#parents' => array_merge($parents, ['vertical_tabs']),
    ];

    // Determine the group that details should be referencing for vertical tabs.
    $group = implode('][', array_merge($parents, ['vertical_tabs']));

    $element['parser'] = [
      '#type' => 'details',
      '#title' => $this->t('Parser'),
      '#tree' => TRUE,
      '#parents' => array_merge($parents, ['parser']),
      '#group' => $group,
      '#weight' => -1,
    ];
    $parserElement = &$element['parser'];

    $parserElement['id'] = [
      '#type' => 'select',
      '#options' => $labels,
      '#default_value' => $parserId,
      '#attributes' => [
        'data-markdown-element' => 'parser',
        'data-markdown-id' => $parserId,
      ],
      '#ajax' => [
        'callback' => '\Drupal\markdown\Form\MarkdownSettingsForm::ajaxChangeParser',
        'event' => 'change',
        'wrapper' => $id,
      ],
    ];

    if ($includeSiteWideOption) {
      if (\Drupal::currentUser()->hasPermission('administer markdown')) {
        $parserElement['id']['#description'] = $this->t('Site-wide parser settings can be adjusted by visiting the <a href=":markdown.settings" target="_blank">@markdown.settings</a> page.', [
          '@markdown.settings' => $this->t('Markdown Settings'),
          ':markdown.settings' => Url::fromRoute('markdown.settings')->toString(),
        ]);
      }
      else {
        $parserElement['id']['#description'] = $this->t('Site-wide parser settings can only be adjusted by administrators.');
      }
    }

    if (!$parserId) {
      return $element;
    }

    // Retrieve the parser.
    $parser = $this->parserManager->createInstance($parserId, $configuration);

    // Indicate if parser is installed.
    $parserElement['id']['#attributes']['data-markdown-installed'] = $parser->isInstalled();

    // Add the parser description.
    $descriptions = [];
    if ($description = $parser->getDescription()) {
      $descriptions[] = $description;
    }
    if ($url = $parser->getUrl()) {
      $descriptions[] = Link::fromTextAndUrl($this->t('[More Info]'), $url)->toString();
    }
    $parserElement['id']['#description'] = Markup::create(implode(' ', $descriptions));

    // Add parser specific settings.
    if ($parser instanceof MarkdownPluginSettingsInterface) {
      $parserElement['settings'] = [
        '#type' => 'details',
        '#title' => $this->t('Settings'),
        '#parents' => array_merge($parents, ['parser', 'settings']),
        '#open' => TRUE,
      ];
      $settingsElement = &$parserElement['settings'];
      $subform_state = SubformState::createForSubform($settingsElement, $element, $form_state);
      $settingsElement = $parser->buildSettingsForm($settingsElement, $subform_state);
      $settingsElement['#access'] = !!Element::getVisibleChildren($settingsElement);
    }

    if ($parser instanceof ExtensibleMarkdownParserInterface) {
      $parserElement = $this->buildExtensions($parser, $parserElement, $form_state, $group);
    }

    return $element;
  }

  protected function buildExtensions(ExtensibleMarkdownParserInterface $parser, array $element, SubformStateInterface $form_state, $group = NULL) {
    $extensions = $parser->extensions();
    if (!$extensions) {
      return $element;
    }

    $parents = $element['#parents'];

    // Add any specific extension settings.
    foreach ($extensions as $extensionId => $extension) {
      $bundled = in_array($extensionId, $parser->getBundledExtensionIds(), TRUE);
      $installed = $extension->isInstalled();
      $enabled = $form_state->getValue(array_merge($parents, ['extensions', $extensionId, 'enabled']), $extension->isEnabled());
      $label = $extension->getLabel();

      $element['extensions'][$extensionId] = [
        '#type' => 'details',
        '#title' => $label,
        '#group' => $group,
      ];
      $extensionElement = &$element['extensions'][$extensionId];

      $descriptions = [];
      if ($description = $extension->getDescription()) {
        $descriptions[] = $description;
      }
      if ($url = $extension->getUrl()) {
        $descriptions[] = Link::fromTextAndUrl($this->t('[More Info]'), $url)->toString();
      }

      // Extension enabled checkbox.
      $extensionElement['enabled'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Enable'),
        '#attributes' => [
          'data-markdown-element' => 'extension',
          'data-markdown-id' => $extensionId,
          'data-markdown-label' => $label,
          'data-markdown-installed' => $installed ? 'true' : 'false',
          'data-markdown-bundle' => $bundled ? $parser->getLabel(FALSE) : 'false',
          'data-markdown-requires' => Json::encode($extension->requires()),
          'data-markdown-required-by' => Json::encode($extension->requiredBy()),
        ],
        '#description' => Markup::create(implode(' ', $descriptions)),
        '#default_value' => $bundled || $enabled,
        '#disabled' => $bundled || !$installed,
      ];

      // Handle extension dependencies.
      if ($requiredBy = $extension->requiredBy()) {
        foreach ($requiredBy as $dependent) {
          $requiredBySelector = $this->getSatesSelector(array_merge($parents, ['extensions', $dependent]), 'enabled');
          $extensionElement['enabled']['#states']['checked'][$requiredBySelector] = ['checked' => TRUE];
          $extensionElement['enabled']['#states']['disabled'][$requiredBySelector] = ['checked' => TRUE];
        }
      }

      // Installed extension settings.
      if ($installed && $extension instanceof MarkdownPluginSettingsInterface) {
        $extensionElement['settings'] = [
          '#type' => 'details',
          '#title' => $this->t('Settings'),
          '#open' => TRUE,
          '#parents' => array_merge($parents, ['extensions', $extensionId, 'settings']),
        ];
        $extensionSettingsElement = &$extensionElement['settings'];
        $subform_state = SubformState::createForSubform($extensionSettingsElement, $element, $form_state);

        $selector = $this->getSatesSelector(array_merge($parents, ['extensions', $extensionId]), 'enabled');

        $extensionSettingsElement['#states'] = [
          'visible' => [
            [$selector => ['checked' => TRUE]],
          ],
        ];

        $extensionSettingsElement = $extension->buildSettingsForm($extensionSettingsElement, $subform_state);
        $extensionSettingsElement['#access'] = !!Element::getVisibleChildren($extensionSettingsElement);
      }
    }

    return $element;
  }

  /**
   * The AJAX callback used to return the parser ajax wrapper.
   */
  public static function ajaxChangeParser(array $form, FormStateInterface $form_state) {
    $parents = array_slice($form_state->getTriggeringElement()['#array_parents'], 0, -2);
    $parents[] = 'ajax';
    return NestedArray::getValue($form, $parents);
  }

  /**
   * Sets the parser identifier.
   *
   * @param string $parserId
   *   The default parser.
   *
   * @return static
   */
  public function setParserId($parserId) {
    $this->parserId = (string) $parserId;
    return $this;
  }

  /**
   * Sets the parser configuration.
   *
   * @param array $configuration
   *   The configuration to set.
   *
   * @return static
   */
  public function setParserConfiguration(array $configuration) {
    $this->parserConfiguration = $configuration;
    return $this;
  }

  /**
   * Retrieves configuration from values.
   *
   * @param array $values
   *   An array of values.
   *
   * @return array
   *   The configuration array.
   */
  public function getConfigurationFromValues(array $values) {
    $defaults = ['id' => '', 'settings' => [], 'extensions' => []];
    $pluginConfiguration = (isset($values['parser']) ? $values['parser'] : $values) + $defaults;
    $parser = static::markdownParserPluginManager()->createInstance($pluginConfiguration['id'], $pluginConfiguration);
    $configuration = $parser->getConfiguration();

    // Sort $configuration by using the $defaults keys. This ensures there
    // is a consistent order when saving the config.
    $configuration = array_replace(array_flip(array_keys(array_intersect_key($defaults, $configuration))), $configuration);

    $this->addDependencies($this->getPluginDependencies($parser));

    return [
      'dependencies' => $this->dependencies,
      'parser' => $configuration,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->cleanValues()->getValues();

    // Normalize parser values into config data.
    $configuration = $this->getConfigurationFromValues($values);

    $this->settings
      ->setData($configuration)
      ->save();

    $this->messenger()->addStatus($this->t('The configuration options have been saved.'));
  }

}
