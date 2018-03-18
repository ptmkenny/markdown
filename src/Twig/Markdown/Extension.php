<?php

namespace Drupal\markdown\Twig\Markdown;

use Drupal\markdown\MarkdownInterface;

/**
 * Class Extension.
 */
class Extension extends \Twig_Extension implements \Twig_Extension_GlobalsInterface {

  /**
   * An instance of a markdown processor to use.
   *
   * @var \Drupal\markdown\MarkdownInterface
   */
  protected $markdown;

  /**
   * {@inheritdoc}
   */
  public function __construct(MarkdownInterface $markdown) {
    $this->markdown = $markdown;
  }

  /**
   * {@inheritdoc}
   */
  public function getName() {
    return 'markdown';
  }

  /**
   * {@inheritdoc}
   */
  public function getGlobals() {
    return [
      'markdown' => $this->markdown,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFilters() {
    return [
      'markdown' => new \Twig_SimpleFilter('markdown', [$this, 'parse'], ['is_safe' => ['html']]),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFunctions() {
    return [
      'markdown' => new \Twig_SimpleFunction('markdown', [$this, 'parse'], ['is_safe' => ['html']]),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getTokenParsers() {
    return [new TokenParser($this->markdown)];
  }

  /**
   * Helper method for parsing markdown.
   *
   * @param string $text
   *   The markdown to parse.
   *
   * @return string
   *   The parsed markdown into HTML.
   */
  public function parse($text) {
    return $this->markdown->parse($text);
  }

}
