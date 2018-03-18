<?php

namespace Drupal\markdown\Plugin\Markdown;

use Drupal\Core\Language\LanguageInterface;
use Drupal\markdown\Plugin\Markdown\BaseMarkdownParser;
use Drupal\markdown\Plugin\Markdown\Extension\CommonMarkRendererInterface;
use League\CommonMark\Block\Parser\BlockParserInterface;
use League\CommonMark\Block\Renderer\BlockRendererInterface;
use League\CommonMark\CommonMarkConverter;
use League\CommonMark\DocumentProcessorInterface;
use League\CommonMark\Environment;
use League\CommonMark\Extension\ExtensionInterface;
use League\CommonMark\Inline\Parser\InlineParserInterface;
use League\CommonMark\Inline\Processor\InlineProcessorInterface;
use League\CommonMark\Inline\Renderer\InlineRendererInterface;

/**
 * Class CommonMark.
 *
 * @MarkdownParser(
 *   id = "commonmark",
 *   label = @Translation("CommonMark"),
 * )
 */
class CommonMark extends BaseMarkdownParser {

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
   * @param null $format
   *
   * @return \League\CommonMark\Converter
   */
  protected function getConverter($format = NULL) {
    $format = $this->getFilterFormat($format);
    $format_id = $format->id();
    if (!isset(static::$converters[$format_id])) {
      $environment = $this->getEnvironment($format);
      static::$converters[$format_id] = new CommonMarkConverter([], $environment);
    }
    return static::$converters[$format_id];
  }

  /**
   * Retrieves current CommonMark environment, creating it if necessary.
   *
   * @param string $format
   *   A filter format identifier or entity instance..
   *
   * @return \League\CommonMark\Environment
   *   The CommonMark Environment instance.
   */
  protected function getEnvironment($format = NULL) {
    $format = $this->getFilterFormat($format);
    $format_id = $format->id();
    if (!isset(static::$environments[$format_id])) {
      $environment = Environment::createCommonMarkEnvironment();
      $filter = $format->filters('markdown');

      foreach ($this->getExtensions($filter) as $extension) {
        if ($extension instanceof ExtensionInterface) {
          $environment->addExtension($extension);
        }

        if ($extension instanceof DocumentProcessorInterface) {
          $environment->addDocumentProcessor($extension);
        }

        if ($extension instanceof InlineProcessorInterface) {
          $environment->addInlineProcessor($extension);
        }

        // Add Block extensions.
        if ($extension instanceof BlockParserInterface || ($extension instanceof BlockRendererInterface && $extension instanceof CommonMarkRendererInterface)) {
          if ($extension instanceof BlockParserInterface) {
            $environment->addBlockParser($extension);
          }
          if ($extension instanceof BlockRendererInterface) {
            $environment->addBlockRenderer($extension->rendererClass(), $extension);
          }
          continue;
        }

        // Add Inline extensions.
        if ($extension instanceof InlineParserInterface || ($extension instanceof InlineRendererInterface && $extension instanceof CommonMarkRendererInterface)) {
          if ($extension instanceof InlineParserInterface) {
            $environment->addInlineParser($extension);
          }
          if ($extension instanceof InlineRendererInterface) {
            $environment->addInlineRenderer($extension->rendererClass(), $extension);
          }
          continue;
        }
      }

      static::$environments[$format_id] = $environment;
    }
    return static::$environments[$format_id];
  }

  /**
   * {@inheritdoc}
   */
  public function getVersion() {
    // @todo Refactor this once CommonMark has a constant we can rely on.
    // @see https://github.com/thephpleague/commonmark/issues/314
    $reflector = new \ReflectionClass(CommonMarkConverter::class);
    $path = dirname(dirname($reflector->getFileName()));
    $changelog = file_get_contents("$path/CHANGELOG.md");
    preg_match('/\[(\d+.\d+.\d+)\]/', $changelog, $matches);
    return $matches && $matches[1];
  }

  /**
   * {@inheritdoc}
   */
  public function isAvailable() {
    return class_exists('League\CommonMark\CommonMarkConverter');
  }

  /**
   * {@inheritdoc}
   */
  public function parse($markdown, LanguageInterface $language = NULL) {
    return $this->getConverter($this->format)->convertToHtml($markdown);
  }

}
