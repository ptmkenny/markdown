<?php

namespace Drupal\markdown\Plugin\Markdown;

use Drupal\Core\Cache\RefinableCacheableDependencyInterface;
use Drupal\Core\Language\LanguageInterface;

/**
 * Interface for defining markdown parsers.
 */
interface ParserInterface extends InstallablePluginInterface, RefinableCacheableDependencyInterface, RenderStrategyInterface, SettingsInterface {

  /**
   * Parses markdown into HTML.
   *
   * @param string $markdown
   *   The markdown string to parse.
   * @param \Drupal\Core\Language\LanguageInterface $language
   *   Optional. The language of the markdown to be parsed.
   *
   * @return \Drupal\markdown\Render\ParsedMarkdownInterface
   *   A safe ParsedMarkdown object.
   *
   * @see \Drupal\markdown\Render\ParsedMarkdownInterface
   */
  public function parse($markdown, LanguageInterface $language = NULL);

}
