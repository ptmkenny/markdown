<?php

namespace Drupal\markdown;

use Drupal\Core\Language\LanguageInterface;

/**
 * Interface MarkdownInterface.
 */
interface MarkdownInterface {

  /**
   * Parse markdown into HTML.
   *
   * @param string $markdown
   *   The markdown string to parse.
   * @param \Drupal\Core\Language\LanguageInterface $language
   *   Optional. The language of the text that is being converted.
   *
   * @return string
   *   The converted markup.
   */
  public function parse($markdown, LanguageInterface $language = NULL);

}
