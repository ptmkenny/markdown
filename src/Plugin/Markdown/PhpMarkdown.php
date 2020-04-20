<?php

namespace Drupal\markdown\Plugin\Markdown;

use Drupal\Core\Language\LanguageInterface;
use Drupal\markdown\Traits\MarkdownParserBenchmarkTrait;

/**
 * @MarkdownParser(
 *   id = "michelf/php-markdown",
 *   label = @Translation("PHP Markdown"),
 *   url = "https://michelf.ca/projects/php-markdown",
 * )
 */
class PhpMarkdown extends BaseParser implements MarkdownParserBenchmarkInterface {

  use MarkdownParserBenchmarkTrait;

  /**
   * The parser class.
   *
   * @var string
   */
  protected static $parserClass = '\\Michelf\\Markdown';

  /**
   * Markdown parsers, keyed by filter identifier.
   *
   * @var \Michelf\Markdown[]
   */
  protected static $parsers = [];

  /**
   * {@inheritdoc}
   */
  public static function installed(): bool {
    return class_exists(static::$parserClass);
  }

  /**
   * {@inheritdoc}
   */
  public static function version(): string {
    if (static::installed()) {
      $class = static::$parserClass;
      return $class::MARKDOWNLIB_VERSION;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function convertToHtml($markdown, LanguageInterface $language = NULL) {
    return $this->getParser()->transform($markdown);
  }

  /**
   * Retrieves the PHP Markdown parser.
   *
   * @return \Michelf\Markdown
   *   A PHP Markdown parser.
   */
  public function getParser() {
    if (!isset(static::$parsers[$this->filterId])) {
      $parser = new static::$parserClass();
      if ($this->filter) {
        foreach ($this->settings as $name => $value) {
          $parser->$name = $value;
        }
      }
      static::$parsers[$this->filterId] = $parser;
    }
    return static::$parsers[$this->filterId];
  }

}
