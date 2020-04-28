<?php

namespace Drupal\markdown\Plugin\Markdown;

/**
 * @MarkdownParser(
 *   id = "michelf/php-markdown-extra",
 *   label = @Translation("PHP Markdown Extra"),
 *   url = "https://michelf.ca/projects/php-markdown/extra",
 *   weight = 30,
 * )
 */
class PhpMarkdownExtra extends PhpMarkdown {

  /**
   * {@inheritdoc}
   */
  protected static $phpMarkdownClass = '\\Michelf\\MarkdownExtra';

}
