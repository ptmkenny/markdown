<?php

namespace Drupal\markdown\Plugin\Markdown;

use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Form\SubformStateInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\markdown\Plugin\Markdown\Extension\CommonMarkExtensionInterface;
use Drupal\markdown\Plugin\Markdown\Extension\CommonMarkRendererInterface;
use League\CommonMark\Block\Parser\BlockParserInterface;
use League\CommonMark\Block\Renderer\BlockRendererInterface;
use League\CommonMark\Environment;
use League\CommonMark\EnvironmentAwareInterface;
use League\CommonMark\Extension\ExtensionInterface;
use League\CommonMark\Inline\Parser\InlineParserInterface;
use League\CommonMark\Inline\Renderer\InlineRendererInterface;

/**
 * @MarkdownParser(
 *   id = "league/commonmark",
 *   label = @Translation("CommonMark"),
 *   description = @Translation("A robust, highly-extensible Markdown parser
 *   for PHP based on the CommonMark specification."), url =
 *   "https://commonmark.thephpleague.com",
 * )
 */
class LeagueCommonMark extends ExtensibleMarkdownParserBase {

  /**
   * The converter class.
   *
   * @var string
   */
  protected static $converterClass = '\\League\\CommonMark\\CommonMarkConverter';

  /**
   * A CommonMark converter instance.
   *
   * @var \League\CommonMark\Converter
   */
  protected $converter;

  /**
   * A CommonMark environment instance.
   *
   * @var \League\CommonMark\Environment
   */
  protected $environment;

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'allow_unsafe_links' => TRUE,
      'enable_em' => TRUE,
      'enable_strong' => TRUE,
      'html_input' => 'escape',
      'max_nesting_level' => 0,
      'renderer' => [
        'block_separator' => '\n',
        'inner_separator' => '\n',
        'soft_break' => '\n',
      ],
      'use_asterisk' => TRUE,
      'use_underscore' => TRUE,
      'unordered_list_markers' => '-, *, +',
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public static function installed() {
    return class_exists(static::$converterClass);
  }

  /**
   * {@inheritdoc}
   */
  public static function version() {
    if (static::installed()) {
      $class = static::$converterClass;
      return $class::VERSION;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function convertToHtml($markdown, LanguageInterface $language = NULL) {
    return $this->converter()->convertToHtml($markdown);
  }

  /**
   * {@inheritdoc}
   */
  public function buildSettingsForm(array $element, SubformStateInterface $form_state) {
    $element = parent::buildSettingsForm($element, $form_state);

    $element['allow_unsafe_links'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Allow unsafe links'),
      '#default_value' => $form_state->getValue('allow_unsafe_links', $this->getSetting('allow_unsafe_links')),
      '#description' => $this->t('Allows potentially risky links and image URLs to remain in the document.'),
    ];

    $element['enable_em'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable emphasis'),
      '#default_value' => $form_state->getValue('enable_em', $this->getSetting('enable_em')),
      '#description' => $this->t('Enables <code>&lt;em&gt;</code> parsing.'),
    ];

    $element['enable_strong'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable strong'),
      '#default_value' => $form_state->getValue('enable_strong', $this->getSetting('enable_strong')),
      '#description' => $this->t('Enables <code>&lt;strong&gt;</code> parsing.'),
    ];

    $element['html_input'] = [
      '#weight' => -1,
      '#type' => 'select',
      '#title' => $this->t('HTML Input'),
      '#default_value' => $form_state->getValue('html_input', $this->getSetting('html_input')),
      '#description' => $this->t('Strategy used when handling raw HTML input.'),
      '#options' => [
        'allow' => $this->t('Allow'),
        'escape' => $this->t('Escape'),
        'strip' => $this->t('Strip'),
      ],
    ];

    $element['max_nesting_level'] = [
      '#type' => 'number',
      '#title' => $this->t('Maximum Nesting Level'),
      '#default_value' => $form_state->getValue('max_nesting_level', $this->getSetting('max_nesting_level')),
      '#description' => $this->t('The maximum nesting level for blocks. Setting this to a positive integer can help protect against long parse times and/or segfaults if blocks are too deeply-nested.'),
      '#min' => 0,
      '#max' => 100000,
    ];

    $element['renderer'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Renderer'),
    ];
    $element['renderer']['block_separator'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Block Separator'),
      '#default_value' => $form_state->getValue(['renderer', 'max_nesting_level'], addcslashes($this->getSetting('renderer.block_separator'), "\n\r\t")),
      '#description' => $this->t('String to use for separating renderer block elements.'),
    ];
    $element['renderer']['inner_separator'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Inner Separator'),
      '#default_value' => $form_state->getValue(['renderer', 'inner_separator'], addcslashes($this->getSetting('renderer.inner_separator'), "\n\r\t")),
      '#description' => $this->t('String to use for separating inner block contents.'),
    ];
    $element['renderer']['soft_break'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Soft Break'),
      '#default_value' => $form_state->getValue(['renderer', 'soft_break'], addcslashes($this->getSetting('renderer.soft_break'), "\n\r\t")),
      '#description' => $this->t('String to use for rendering soft breaks.'),
    ];

    $element['use_asterisk'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Use Asterisk'),
      '#default_value' => $form_state->getValue('use_asterisk', $this->getSetting('use_asterisk')),
      '#description' => $this->t('Enables parsing of <code>*</code> for emphasis.'),
    ];

    $element['use_underscore'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Use Underscore'),
      '#default_value' => $form_state->getValue('use_underscore', $this->getSetting('use_underscore')),
      '#description' => $this->t('Enables parsing of <code>_</code> for emphasis.'),
    ];

    $unorderedListMarkers = $form_state->getValue('unordered_list_markers', $this->getSetting('unordered_list_markers', []));
    $element['unordered_list_markers'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Unordered List Markers'),
      '#default_value' => is_array($unorderedListMarkers) ? implode(', ', $unorderedListMarkers) : $unorderedListMarkers,
      '#description' => $this->t('Characters, separated by commas, that are used to indicated a bulleted list.'),
    ];

    return $element;
  }

  /**
   * Retrieves a CommonMark converter instance.
   *
   * @return \League\CommonMark\Converter
   *   A CommonMark converter.
   */
  protected function converter() {
    if (!$this->converter) {
      $this->converter = new static::$converterClass($this->getSettings(), $this->getEnvironment());
    }
    return $this->converter;
  }

  /**
   * Creates an environment.
   *
   * @return \League\CommonMark\ConfigurableEnvironmentInterface
   */
  protected function createEnvironment() {
    return Environment::createCommonMarkEnvironment();
  }

  /**
   * {@inheritdoc}
   */
  public function extensionInterfaces() {
    return [CommonMarkExtensionInterface::class];
  }

  /**
   * {@inheritdoc}
   */
  public function getConfiguration() {
    $configuration = parent::getConfiguration();

    // Escape newlines.
    if (isset($configuration['settings']['renderer']) && is_array($configuration['settings']['renderer'])) {
      foreach ($configuration['settings']['renderer'] as &$setting) {
        $setting = addcslashes($setting, "\n\r\t");
      }
    }

    // Implode unordered list markers into a string.
    if (isset($configuration['settings']['unordered_list_markers']) && is_array($configuration['settings']['unordered_list_markers'])) {
      $configuration['settings']['unordered_list_markers'] = implode(', ', $configuration['settings']['unordered_list_markers']);
    }

    // Set infinite max nesting level to 0.
    if (isset($configuration['settings']['max_nesting_level']) && $configuration['settings']['max_nesting_level'] === INF) {
      $configuration['settings']['max_nesting_level'] = 0;
    }

    return $configuration;
  }

  /**
   * Retrieves a CommonMark environment, creating it if necessary.
   *
   * @return \League\CommonMark\Environment
   *   The CommonMark environment.
   */
  protected function getEnvironment() {
    if (!$this->environment) {
      $environment = $this->createEnvironment();
      $extensions = $this->extensions();
      /** @var \Drupal\markdown\Plugin\Markdown\Extension\MarkdownExtensionInterface $extension */
      foreach ($extensions as $extension) {
        // Skip disabled extensions.
        if (!$extension->isEnabled()) {
          continue;
        }
        if ($extension instanceof MarkdownPluginSettingsInterface) {
          // Because CommonMark is highly extensible, any extension that
          // implements settings should provide a specific and unique settings
          // key to wrap its settings when passing it to the environment config.
          // In the off chance the extension absolutely must merge with the
          // root level, it can pass an empty value (i.e. '' or 0); NULL will
          // throw an exception and FALSE will ignore merging with the parsing
          // config altogether.
          $settingsKey = $extension->extensionSettingsKey();
          if ($settingsKey === NULL) {
            throw new InvalidPluginDefinitionException($extension->getPluginId(), sprintf('The "%s" markdown extension must also supply a value in %s. This is a requirement of the parser so it knows how extension settings should be merged.', $extension->getPluginId(), '\Drupal\markdown\Plugin\Markdown\MarkdownPluginSettingsInterface::parserExtensionSettingsKey'));
          }

          // If the extension plugin specifies anything other than FALSE, merge.
          if ($settingsKey !== FALSE) {
            $settings = $settingsKey ? [$settingsKey => $extension->getSettings()] : $extension->getSettings();
            $environment->setConfig(NestedArray::mergeDeep($environment->getConfig(), $settings));
          }
        }

        // Allow standalone extensions to be aware of the environment.
        // This allows extensions to load external instances that may not be
        // able to be extended from base Drupal plugin class (which is needed
        // for discovery purposes).
        if ($extension instanceof EnvironmentAwareInterface && !$extension instanceof BlockParserInterface && !$extension instanceof InlineParserInterface) {
          $extension->setEnvironment($environment);
        }

        if ($extension instanceof ExtensionInterface) {
          $environment->addExtension($extension);
        }

        // Add Block extensions.
        if ($extension instanceof BlockParserInterface || ($extension instanceof BlockRendererInterface && $extension instanceof CommonMarkRendererInterface)) {
          if ($extension instanceof BlockParserInterface) {
            $environment->addBlockParser($extension);
          }
          if ($extension instanceof BlockRendererInterface) {
            $environment->addBlockRenderer($extension->rendererClass(), $extension);
          }
        }

        // Add Inline extensions.
        if ($extension instanceof InlineParserInterface || ($extension instanceof InlineRendererInterface && $extension instanceof CommonMarkRendererInterface)) {
          if ($extension instanceof InlineParserInterface) {
            $environment->addInlineParser($extension);
          }
          if ($extension instanceof InlineRendererInterface) {
            $environment->addInlineRenderer($extension->rendererClass(), $extension);
          }
        }
      }

      $this->environment = $environment;
    }
    return $this->environment;
  }

  /**
   * {@inheritdoc}
   */
  public function setConfiguration(array $configuration) {
    // Convert newlines to actual newlines.
    if (isset($configuration['settings']['renderer'])) {
      foreach ($configuration['settings']['renderer'] as &$setting) {
        $setting = stripcslashes($setting);
      }
    }

    // Explode unordered list markers into an array.
    if (isset($configuration['settings']['unordered_list_markers'])) {
      $configuration['settings']['unordered_list_markers'] = array_map('trim', explode(',', $configuration['settings']['unordered_list_markers']));
    }

    // Set the max nesting level to infinite if not a positive number.
    if (isset($configuration['settings']['max_nesting_level']) && $configuration['settings']['max_nesting_level'] <= 0) {
      $configuration['settings']['max_nesting_level'] = INF;
    }
    return parent::setConfiguration($configuration);
  }

}
