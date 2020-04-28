<?php

namespace Drupal\markdown\Form;

use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\SubformState;
use Drupal\Core\Form\SubformStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Render\Element;
use Drupal\Core\Render\Markup;
use Drupal\Core\Url;
use Drupal\markdown\MarkdownParserPluginManagerInterface;
use Drupal\markdown\MarkdownSettingsInterface;
use Drupal\markdown\Plugin\Markdown\ExtensibleMarkdownParserInterface;
use Drupal\markdown\Traits\MarkdownStatesTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

class MarkdownSettingsForm extends FormBase {

  use MarkdownStatesTrait;

  /**
   * The Markdown Settings.
   *
   * @var \Drupal\markdown\MarkdownSettingsInterface
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
  public function __construct(MarkdownSettingsInterface $settings, MarkdownParserPluginManagerInterface $parserManager) {
    $this->settings = $settings;
    $this->parserManager = $parserManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container = NULL) {
    if (!$container) {
      $container = \Drupal::getContainer();
    }
    return new static(
      $container->get('markdown.settings'),
      $container->get('plugin.manager.markdown.parser')
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
    $subform = ['#parents' => []];

    $subform_state = SubformState::createForSubform($subform, $form, $form_state);

    $form['markdown'] = $this->buildSettings($subform, $subform_state);

    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save configuration'),
      '#button_type' => 'primary',
    ];

    // By default, render the form using system-config-form.html.twig.
    $form['#theme'] = 'system_config_form';

    return $form;
  }

  public function buildSettings(array $element, SubformStateInterface $form_state) {
    // Immediately return if there are no installed parsers.
    if (!($labels = $this->parserManager->labels())) {
      $element['parser'] = [
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

    $parents = isset($element['#parents']) ? $element['#parents'] : [];
    $defaultParser = $form_state->getValue(array_merge($parents, ['parser', 'id']), $this->getParserId());
    $defaultParserConfiguration = NestedArray::mergeDeep($this->getParserConfiguration(), $form_state->getValue('parser', []));
    $id = Html::getUniqueId('markdown-parser-ajax');

    // Build a wrapper for the ajax response.
    $element['ajax'] = [
      '#type' => 'container',
      '#attributes' => ['id' => $id],
      '#parents' => $parents,
      '#tree' => TRUE,
    ];

    $element['ajax']['parser'] = [
      '#type' => 'container',
      '#parents' => array_merge($parents, ['parser']),
    ];

    $element['ajax']['parser']['id'] = [
      '#type' => 'select',
      '#title' => $this->t('Parser'),
      '#options' => $labels,
      '#default_value' => $defaultParser,
      '#ajax' => [
        'callback' => '\Drupal\markdown\Form\MarkdownSettingsForm::ajaxChangeParser',
        'event' => 'change',
        'wrapper' => $id,
      ],
    ];

    if ($includeSiteWideOption) {
      if (\Drupal::currentUser()->hasPermission('administer markdown')) {
        $element['ajax']['parser']['id']['#description'] = $this->t('Site-wide parser settings can be adjusted by visiting the <a href=":markdown.settings" target="_blank">@markdown.settings</a> page.', [
          '@markdown.settings' => $this->t('Markdown Settings'),
          ':markdown.settings' => Url::fromRoute('markdown.settings')->toString(),
        ]);
      }
      else {
        $element['ajax']['parser']['id']['#description'] = $this->t('Site-wide parser settings can only be adjusted by administrators.');
      }
    }

    if (!$defaultParser) {
      return $element;
    }

    // Retrieve the parser.
    $parser = $this->parserManager->createInstance($defaultParser, $defaultParserConfiguration);

    // Add the parser description.
    $descriptions = [];
    if ($description = $parser->getDescription()) {
      $descriptions[] = $description;
    }
    if ($url = $parser->getUrl()) {
      $descriptions[] = Link::fromTextAndUrl($this->t('[More Info]'), $url)->toString();
    }
    $element['ajax']['parser']['id']['#description'] = Markup::create(implode(' ', $descriptions));

    // Add parser specific settings.
    $element['ajax']['parser']['settings'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Settings'),
      '#parents' => array_merge($parents, ['parser', 'settings']),
    ];
    $subform_state = SubformState::createForSubform($element['ajax']['parser']['settings'], $element, $form_state);
    $element['ajax']['parser']['settings'] = $parser->buildSettingsForm($element['ajax']['parser']['settings'], $subform_state);
    $element['ajax']['parser']['settings']['#access'] = !!Element::getVisibleChildren($element['ajax']['parser']['settings']);

    if ($parser instanceof ExtensibleMarkdownParserInterface && ($extensions = $parser->getExtensions())) {
      // Add any specific extension settings.
      $element['ajax']['parser']['extensions'] = [
        '#type' => 'fieldset',
        '#title' => $this->t('Extensions'),
        '#parents' => array_merge($parents, ['parser', 'extensions']),
      ];
      foreach ($extensions as $pluginId => $extension) {
        $enabled = $extension->isEnabled();
        $label = $extension->getLabel();
        $descriptions = [];
        if ($description = $extension->getDescription()) {
          $descriptions[] = $description;
        }
        if ($url = $extension->getUrl()) {
          $descriptions[] = Link::fromTextAndUrl($this->t('[More Info]'), $url)->toString();
        }
        if ($disabled = !$extension->isInstalled()) {
          $descriptions[] = $this->t('(Not Installed)');
        }

        // Extension enabled checkbox.
        $element['ajax']['parser']['extensions'][$pluginId]['enabled'] = [
          '#type' => 'checkbox',
          '#title' => $label,
          '#description' => Markup::create(implode(' ', $descriptions)),
          '#default_value' => $enabled,
          '#disabled' => $disabled,
        ];

        // Extension settings.
        $element['ajax']['parser']['extensions'][$pluginId]['settings'] = [
          '#type' => 'details',
          '#title' => $this->t('@label Settings', ['@label' => $label]),
          '#open' => $enabled,
          '#parents' => array_merge($parents, ['parser', 'extensions', $pluginId, 'settings']),
        ];
        $subform_state = SubformState::createForSubform($element['ajax']['parser']['extensions'][$pluginId]['settings'], $element, $form_state);

        $selector = $this->getSatesSelector(array_merge($parents, ['parser', 'extensions', $pluginId]), 'enabled');
        $element['ajax']['parser']['extensions'][$pluginId]['settings']['#states'] = [
          'visible' => [
            $selector => ['checked' => TRUE],
          ],
        ];

        $element['ajax']['parser']['extensions'][$pluginId]['settings'] = $extension->buildSettingsForm($element['ajax']['parser']['extensions'][$pluginId]['settings'], $subform_state);
        $element['ajax']['parser']['extensions'][$pluginId]['settings']['#access'] = !!Element::getVisibleChildren($element['ajax']['parser']['extensions'][$pluginId]['settings']);
      }
    }

    return $element;
  }

  /**
   * The AJAX callback used to return the parser ajax wrapper.
   */
  public static function ajaxChangeParser(array $form, FormStateInterface $form_state) {
    $parents = array_slice($form_state->getTriggeringElement()['#array_parents'], 0, -2);
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
   * Normalizes config parser values.
   *
   * @param array $parser
   *   An array of parser values, passed by reference.
   *
   * @return array
   *   The normalized config values.
   */
  public static function normalizeConfigParserValues(array $parser) {
    $config = ['id' => $parser['id'], 'settings' => $parser['settings']];

    // Normalize extensions and their settings.
    $extensions = [];
    if (!empty($parser['extensions'])) {
      foreach ($parser['extensions'] as $id => $extension) {
        // Skip disabled extensions.
        if (isset($extension['enabled']) && empty($extension['enabled'])) {
          continue;
        }

        $extension += ['settings' => []];

        // Remove enabled property, all extensions stored in config are enabled.
        unset($extension['enabled']);

        // Prepend the extension identifier. This is necessary so
        // markdown_extension_settings.* schema can work.
        $extensions[] = array_merge(['id' => $id], $extension);
      }
    }

    // Only add extensions if there some enabled.
    if (!empty($extensions)) {
      $config['extensions'] = $extensions;
    }

    return $config;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->cleanValues()->getValues() + ['parser' => []];

    // Normalize parser values into config data.
    $parser = static::normalizeConfigParserValues($values['parser']);

    $this->config('markdown.settings')
      ->setData(['parser' => $parser])
      ->save();

    $this->messenger()->addStatus($this->t('The configuration options have been saved.'));
  }

}
