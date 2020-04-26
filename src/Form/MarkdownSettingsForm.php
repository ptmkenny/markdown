<?php

namespace Drupal\markdown\Form;

use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\SubformState;
use Drupal\Core\Form\SubformStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Render\Element;
use Drupal\Core\Render\Markup;
use Drupal\Core\Url;
use Drupal\markdown\MarkdownParserPluginManagerInterface;
use Drupal\markdown\Plugin\Markdown\ExtensibleMarkdownParserInterface;
use Drupal\markdown\Traits\MarkdownStatesTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

class MarkdownSettingsForm extends ConfigFormBase {

  use MarkdownStatesTrait;

  /**
   * The default parser.
   *
   * @var string
   */
  protected $defaultParser;

  /**
   * The default parser configuration.
   *
   * @var array
   */
  protected $defaultParserConfiguration;

  /**
   * The Markdown Parser Plugin Manager service.
   *
   * @var \Drupal\markdown\MarkdownParserPluginManagerInterface
   */
  protected $parserManager;

  /**
   * {@inheritdoc}
   */
  public function __construct(ConfigFactoryInterface $config_factory, MarkdownParserPluginManagerInterface $parserManager) {
    parent::__construct($config_factory);
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
      $container->get('config.factory'),
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
   * Retrieves the default parser.
   *
   * @return string
   */
  public function getDefaultParser() {
    if (!$this->defaultParser) {
      $this->defaultParser = $this->config('markdown.settings')->get('parser.id') ?: current(array_keys($this->parserManager->installedDefinitions()));
    }
    return $this->defaultParser;
  }

  /**
   * Retrieves the default parser configuration.
   *
   * @return array
   */
  public function getDefaultParserConfiguration() {
    if (!$this->defaultParserConfiguration) {
      $this->defaultParserConfiguration = $this->config('markdown.settings')->get('parser') ?: [];
    }
    return $this->defaultParserConfiguration;
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
    $form = parent::buildForm($form, $form_state);
    $form += [
      '#parents' => [],
      '#title' => $this->t('Markdown'),
    ];
    $subform = ['#parents' => []];

    $subform_state = SubformState::createForSubform($subform, $form, $form_state);
    $form['markdown'] = $this->buildSettings($subform, $subform_state);
    return $form;
  }

  public function buildSettings(array $element, SubformStateInterface $form_state) {
    // Immediately return if there are no installed parsers.
    if (!($labels = $this->parserManager->getLabels())) {
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

    $parents = isset($element['#parents']) ? $element['#parents'] : [];
    $defaultParser = $form_state->getValue(array_merge($parents, ['parser', 'id']), $this->getDefaultParser());
    $defaultParserConfiguration = NestedArray::mergeDeep($this->getDefaultParserConfiguration(), $form_state->getValue('parser', []));
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
        'callback' => [$this, 'ajaxChangeParser'],
        'event' => 'change',
        'wrapper' => $id,
      ],
    ];

    if (!$defaultParser) {
      return $element;
    }

    $parser = $this->parserManager->createInstance($defaultParser, $defaultParserConfiguration);

    $descriptions = [];
    if ($description = $parser->getDescription()) {
      $descriptions[] = $description;
    }
    if ($url = $parser->getUrl()) {
      $descriptions[] = Link::fromTextAndUrl($this->t('[More Info]'), $url)->toString();
    }

    $element['ajax']['parser']['id']['#description'] = Markup::create(implode(' ', $descriptions));

    // @todo Add parser specific settings.
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
  public function ajaxChangeParser(array $form, FormStateInterface $form_state) {
    $parents = $form_state->getTriggeringElement()['#array_parents'];
    array_pop($parents);
    return NestedArray::getValue($form, $parents);
  }

  /**
   * Sets the default parser.
   *
   * @param string $defaultParser
   *   The default parser.
   *
   * @return static
   */
  public function setDefaultParser($defaultParser) {
    $this->defaultParser = (string) $defaultParser;
    return $this;
  }

  /**
   * Sets the default parser configuration.
   *
   * @param array $configuration
   *   The configuration to set.
   *
   * @return static
   */
  public function setDefaultParserConfiguration(array $configuration) {
    $this->defaultParserConfiguration = $configuration;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->cleanValues()->getValues();

    // Filter disabled extension settings.
    if (!empty($values['parser']['extensions'])) {
      foreach ($values['parser']['extensions'] as $id => $settings) {
        if (empty($settings['enabled'])) {
          unset($values['parser']['extensions'][$id]);
        }
      }
    }

    if (empty($values['parser']['extensions'])) {
      unset($values['parser']['extensions']);
    }

    $this->config('markdown.settings')
      ->setData($values)
      ->save();

    parent::submitForm($form, $form_state);
  }

}
