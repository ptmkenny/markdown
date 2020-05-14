<?php

namespace Drupal\markdown\Plugin\Markdown\CommonMark;

use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\markdown\Form\SubformState;
use Drupal\markdown\Plugin\Markdown\BaseExtensibleParser;
use Drupal\markdown\Plugin\Markdown\SettingsInterface;
use Drupal\markdown\Util\KeyValuePipeConverter;
use League\CommonMark\Block\Parser\BlockParserInterface;
use League\CommonMark\Block\Renderer\BlockRendererInterface;
use League\CommonMark\Environment;
use League\CommonMark\EnvironmentAwareInterface;
use League\CommonMark\Extension\ExtensionInterface;
use League\CommonMark\Inline\Parser\InlineParserInterface;
use League\CommonMark\Inline\Renderer\InlineRendererInterface;

/**
 * Support for CommonMark by The League of Extraordinary Packages.
 *
 * @MarkdownParser(
 *   id = "league/commonmark",
 *   label = @Translation("CommonMark"),
 *   description = @Translation("A robust, highly-extensible Markdown parser for PHP based on the CommonMark specification."),
 *   installed = "\League\CommonMark\CommonMarkConverter",
 *   version = "\League\CommonMark\CommonMarkConverter::VERSION",
 *   versionConstraint = "^1.3 || ^2.0",
 *   url = "https://commonmark.thephpleague.com",
 *   extensionInterfaces = {
 *     "\Drupal\markdown\Plugin\Markdown\CommonMark\ExtensionInterface",
 *   },
 * )
 */
class CommonMark extends BaseExtensibleParser {

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
        'block_separator' => "\n",
        'inner_separator' => "\n",
        'soft_break' => "\n",
      ],
      'use_asterisk' => TRUE,
      'use_underscore' => TRUE,
      'unordered_list_markers' => ['-', '*', '+'],
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  protected function convertToHtml($markdown, LanguageInterface $language = NULL) {
    return $this->converter()->convertToHtml($markdown);
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $element, FormStateInterface $form_state) {
    /** @var \Drupal\markdown\Form\SubformStateInterface $form_state */
    $element = parent::buildConfigurationForm($element, $form_state);

    $element += $this->createSettingElement('allow_unsafe_links', [
      '#type' => 'checkbox',
      '#description' => $this->t('Allows potentially risky links and image URLs to remain in the document.'),
    ], $form_state);
    $this->renderStrategyDisabledSettingState($form_state, $element['allow_unsafe_links']);

    $element += $this->createSettingElement('enable_em', [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable emphasis'),
      '#description' => $this->t('Enables <code>&lt;em&gt;</code> parsing.'),
    ], $form_state);

    $element += $this->createSettingElement('enable_strong', [
      '#type' => 'checkbox',
      '#description' => $this->t('Enables <code>&lt;strong&gt;</code> parsing.'),
    ], $form_state);

    $element += $this->createSettingElement('html_input', [
      '#weight' => -1,
      '#type' => 'select',
      '#title' => $this->t('HTML Input'),
      '#description' => $this->t('Strategy to use when handling raw HTML input.'),
      '#options' => [
        'allow' => $this->t('Allow'),
        'escape' => $this->t('Escape'),
        'strip' => $this->t('Strip'),
      ],
    ], $form_state);
    $this->renderStrategyDisabledSettingState($form_state, $element['html_input']);

    // Always allow html_input when using a render strategy.
    if ($this->getRenderStrategy() !== static::NONE) {
      $element['html_input']['#value'] = 'allow';
    }

    $element += $this->createSettingElement('max_nesting_level', [
      '#type' => 'number',
      '#description' => $this->t('The maximum nesting level for blocks. Setting this to a positive integer can help protect against long parse times and/or segfaults if blocks are too deeply-nested.'),
      '#min' => 0,
      '#max' => 100000,
    ], $form_state, 'intval');

    $element['renderer'] = [
      '#type' => 'container',
    ];
    $rendererSubformState = SubformState::createForSubform($element['renderer'], $element, $form_state);

    $element['renderer'] += $this->createSettingElement('renderer.block_separator', [
      '#type' => 'textfield',
      '#description' => $this->t('String to use for separating renderer block elements.'),
    ], $rendererSubformState, '\Drupal\markdown\Plugin\Markdown\CommonMark\CommonMark::addcslashes');

    $element['renderer'] += $this->createSettingElement('renderer.inner_separator', [
      '#type' => 'textfield',
      '#description' => $this->t('String to use for separating inner block contents.'),
    ], $rendererSubformState, '\Drupal\markdown\Plugin\Markdown\CommonMark\CommonMark::addcslashes');

    $element['renderer'] += $this->createSettingElement('renderer.soft_break', [
      '#type' => 'textfield',
      '#description' => $this->t('String to use for rendering soft breaks.'),
    ], $rendererSubformState, '\Drupal\markdown\Plugin\Markdown\CommonMark\CommonMark::addcslashes');

    $element += $this->createSettingElement('use_asterisk', [
      '#type' => 'checkbox',
      '#description' => $this->t('Enables parsing of <code>*</code> for emphasis.'),
    ], $form_state);

    $element += $this->createSettingElement('use_underscore', [
      '#type' => 'checkbox',
      '#description' => $this->t('Enables parsing of <code>_</code> for emphasis.'),
    ], $form_state);

    $element += $this->createSettingElement('unordered_list_markers', [
      '#type' => 'textarea',
      '#description' => $this->t('Characters that are used to indicated a bulleted list; only one character per line.'),
    ], $form_state, '\Drupal\markdown\Util\KeyValuePipeConverter::denormalizeNoKeys');

    return $element;
  }

  /**
   * Wrapper method to assist with setting values in form.
   *
   * @param string $string
   *   The string to add slashes.
   * @param string $charlist
   *   The character list that slashes will be added to.
   *
   * @return string
   *   The modified string.
   */
  public static function addcslashes($string, $charlist = "\n\r\t") {
    return \addcslashes($string, $charlist);
  }

  /**
   * Retrieves a CommonMark converter instance.
   *
   * @return \League\CommonMark\Converter
   *   A CommonMark converter.
   */
  public function converter() {
    if (!$this->converter) {
      $this->converter = new static::$converterClass($this->getSettings(TRUE), $this->getEnvironment());
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
  public function getConfiguration() {
    $configuration = parent::getConfiguration();

    // Unless the render strategy is set to "none", force the following
    // settings so the parser doesn't attempt to filter things.
    if ($this->getRenderStrategy() !== static::NONE) {
      $configuration['settings']['allow_unsafe_links'] = TRUE;
      $configuration['settings']['html_input'] = 'allow';
    }

    // Escape newlines.
    if (isset($configuration['settings']['renderer']) && is_array($configuration['settings']['renderer'])) {
      foreach ($configuration['settings']['renderer'] as &$setting) {
        $setting = addcslashes($setting, "\n\r\t");
      }
    }

    // Set infinite max nesting level to 0.
    if (isset($configuration['settings']['max_nesting_level']) && $configuration['settings']['max_nesting_level'] === INF) {
      $configuration['settings']['max_nesting_level'] = 0;
    }

    // Normalize settings from a key|value string into an associative array.
    foreach (['unordered_list_markers'] as $name) {
      if (isset($configuration['settings'][$name])) {
        $configuration['settings'][$name] = KeyValuePipeConverter::normalize($configuration['settings'][$name]);
      }
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
      $settings = $this->getSettings(TRUE);

      // Unless the render strategy is set to "none", force the following
      // settings so the parser doesn't attempt to filter things.
      if ($this->getRenderStrategy() !== static::NONE) {
        $settings['allow_unsafe_links'] = TRUE;
        $settings['html_input'] = 'allow';
      }

      // Merge in parser settings.
      $environment->setConfig(NestedArray::mergeDeep($environment->getConfig(), $settings));

      $extensions = $this->extensions();
      /** @var \Drupal\markdown\Plugin\Markdown\Extension\MarkdownExtensionInterface $extension */
      foreach ($extensions as $extension) {
        // Skip disabled extensions.
        if (!$extension->isEnabled()) {
          continue;
        }
        if ($extension instanceof SettingsInterface) {
          // Because CommonMark is highly extensible, any extension that
          // implements settings should provide a specific and unique settings
          // key to wrap its settings when passing it to the environment config.
          // In the off chance the extension absolutely must merge with the
          // root level, it can pass an empty value (i.e. '' or 0); NULL will
          // throw an exception and FALSE will ignore merging with the parsing
          // config altogether.
          $settingsKey = $extension->settingsKey();
          if ($settingsKey === NULL) {
            throw new InvalidPluginDefinitionException($extension->getPluginId(), sprintf('The "%s" markdown extension must also supply a value in %s. This is a requirement of the parser so it knows how extension settings should be merged.', $extension->getPluginId(), '\Drupal\markdown\Plugin\Markdown\MarkdownPluginSettingsInterface::parserExtensionSettingsKey'));
          }

          // If the extension plugin specifies anything other than FALSE, merge.
          if ($settingsKey !== FALSE) {
            $extensionSettings = $extension->getSettings(TRUE);
            if ($settingsKey) {
              $extensionSettings = [$settingsKey => $extensionSettings];
            }
            $environment->setConfig(NestedArray::mergeDeep($environment->getConfig(), $extensionSettings));
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
        if ($extension instanceof BlockParserInterface || ($extension instanceof BlockRendererInterface && $extension instanceof RendererInterface)) {
          if ($extension instanceof BlockParserInterface) {
            $environment->addBlockParser($extension);
          }
          if ($extension instanceof BlockRendererInterface) {
            $environment->addBlockRenderer($extension->rendererClass(), $extension);
          }
        }

        // Add Inline extensions.
        if ($extension instanceof InlineParserInterface || ($extension instanceof InlineRendererInterface && $extension instanceof RendererInterface)) {
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

    // Set the max nesting level to infinite if not a positive number.
    if (isset($configuration['settings']['max_nesting_level']) && $configuration['settings']['max_nesting_level'] <= 0) {
      $configuration['settings']['max_nesting_level'] = INF;
    }

    // Normalize settings from a key|value string into an associative array.
    foreach (['unordered_list_markers'] as $name) {
      if (isset($configuration['settings'][$name])) {
        $configuration['settings'][$name] = KeyValuePipeConverter::normalize($configuration['settings'][$name]);
      }
    }

    return parent::setConfiguration($configuration);
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::validateConfigurationForm($form, $form_state);
    if ($unorderedListMarkers = $form_state->getValue('unordered_list_markers')) {
      $unorderedListMarkers = KeyValuePipeConverter::normalize($unorderedListMarkers);
      foreach ($unorderedListMarkers as $marker) {
        if (strlen($marker) > 1) {
          $form_state->setError($form['unordered_list_markers'], $this->t('The Unordered List Markers must be only one character per line.'));
        }
      }
    }
  }

}
