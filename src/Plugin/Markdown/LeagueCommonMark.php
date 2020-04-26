<?php

namespace Drupal\markdown\Plugin\Markdown;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Form\SubformStateInterface;
use Drupal\Core\Language\LanguageInterface;
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
 *   id = "thephpleague/commonmark",
 *   label = @Translation("PHP CommonMark"),
 *   description = @Translation("A robust, highly-extensible Markdown parser for PHP based on the CommonMark specification."),
 *   url = "https://commonmark.thephpleague.com",
 * )
 */
class LeagueCommonMark extends ExtensibleParser {

  /**
   * The converter class.
   *
   * @var string
   */
  protected static $converterClass = '\\League\\CommonMark\\CommonMarkConverter';

  /**
   * CommonMark converters, keyed by format filter identifiers.
   *
   * @var \League\CommonMark\Converter[]
   */
  protected static $converters;

  /**
   * A CommonMark environment, keyed by format filter identifiers.
   *
   * @var \League\CommonMark\Environment[]
   */
  protected static $environments;

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return NestedArray::mergeDeep(
      parent::defaultSettings(),
      [
        // @todo finish adding the rest of the config settings
        // @see https://commonmark.thephpleague.com/1.4/configuration/
        'renderer' => [
          'block_separator' => "\n",
          'inner_separator' => "\n",
          'soft_break' => "\n",
        ],
        'html_input' => 'allow',
        'allow_unsafe_links' => TRUE,
        'max_nesting_level' => 100,
      ]
    );
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
    return $this->getConverter()->convertToHtml($markdown);
  }

  /**
   * {@inheritdoc}
   */
  public function buildSettingsForm(array $element, SubformStateInterface $form_state) {
    $element = parent::buildSettingsForm($element, $form_state);

    // @todo finish adding the rest of the config settings
    // @see https://commonmark.thephpleague.com/1.4/configuration/
    $element['allow_unsafe_links'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Allow unsafe links'),
      '#default_value' => $form_state->getValue('allow_unsafe_links', $this->getSetting('allow_unsafe_links')),
    ];

    return $element;
  }

  /**
   * Retrieves a CommonMark converter, creating it if necessary.
   *
   * @return \League\CommonMark\Converter
   *   A CommonMark converter.
   */
  protected function getConverter() {
    if (!isset(static::$converters[$this->filterId])) {
      $environment = $this->getEnvironment();
      static::$converters[$this->filterId] = new static::$converterClass($this->settings, $environment);
    }
    return static::$converters[$this->filterId];
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
   * Retrieves a CommonMark environment, creating it if necessary.
   *
   * @return \League\CommonMark\Environment
   *   The CommonMark environment.
   */
  protected function getEnvironment() {
    if (!isset(static::$environments[$this->filterId])) {
      $environment = $this->createEnvironment();
      $extensions = $this->getExtensions(TRUE);
      foreach ($extensions as $extension) {
        if ($settings = $extension->getSettings()) {
          $environment->setConfig(NestedArray::mergeDeep($environment->getConfig(), $settings));
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

      static::$environments[$this->filterId] = $environment;
    }
    return static::$environments[$this->filterId];
  }

}
