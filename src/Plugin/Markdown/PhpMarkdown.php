<?php

namespace Drupal\markdown\Plugin\Markdown;

use Drupal\Core\Language\LanguageInterface;
use Michelf\MarkdownExtra;

/**
 * Class PhpMarkdown.
 *
 * @MarkdownParser(
 *   id = "php_markdown",
 *   label = @Translation("PHP Markdown"),
 *   checkClass = "Michelf\MarkdownExtra",
 * )
 */
class PhpMarkdown extends BaseMarkdownParser {

  /**
   * MarkdownExtra parsers, keyed by filter identifier.
   *
   * @var \Michelf\MarkdownExtra[]
   */
  protected static $parsers = [];

  /**
   * Retrieves the PHP Markdown parser.
   *
   * @return \Michelf\MarkdownExtra
   *   A PHP Markdown parser.
   */
  public function getParser() {
    if (!isset(static::$parsers[$this->filterId])) {
      $parser = new MarkdownExtra();
      if ($this->filter) {
        foreach ($this->settings as $name => $value) {
          $parser->$name = $value;
        }
      }
      static::$parsers[$this->filterId] = $parser;
    }
    return static::$parsers[$this->filterId];
  }

  /**
   * {@inheritdoc}
   */
  public function getVersion() {
    return MarkdownExtra::MARKDOWNLIB_VERSION;
  }

  /**
   * {@inheritdoc}
   */
  public function parse($markdown, LanguageInterface $language = NULL) {
    return $this->getParser()->transform($markdown);
  }

}
