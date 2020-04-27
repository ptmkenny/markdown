<?php

namespace Drupal\markdown;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Language\LanguageInterface;

/**
 * Interface MarkdownInterface.
 */
interface MarkdownInterface extends ContainerInjectionInterface {

  /**
   * Loads a cached ParsedMarkdown object.
   *
   * @param string $id
   *   A unique identifier that will be used to cache the parsed markdown.
   */
  public function load($id);

  /**
   * Loads a cached ParsedMarkdown object based on a file system path.
   *
   * @param string $path
   *   The local file system path of a markdown file to parse if the cached
   *   ParsedMarkdown object doesn't yet exist. Once parsed, its identifier
   *   will be set to the provided $id and then cached.
   * @param string $id
   *   Optional. A unique identifier for caching the parsed markdown. If not
   *   set, one will be generated automatically based on the provided $path.
   * @param \Drupal\Core\Language\LanguageInterface $language
   *   Optional. The language of the markdown that is being parsed.
   *
   * @return \Drupal\markdown\ParsedMarkdownInterface
   *   A ParsedMarkdown object.
   *
   * @throws \Drupal\markdown\Exception\MarkdownFileNotExistsException
   */
  public function loadPath($path, $id = NULL, LanguageInterface $language = NULL);

  /**
   * Loads a cached ParsedMarkdown object based on a URL.
   *
   * @param string $url
   *   The external URL of a markdown file to parse if the cached
   *   ParsedMarkdown object doesn't yet exist. Once parsed, its identifier
   *   will be set to the provided $id and then cached.
   * @param string $id
   *   Optional. A unique identifier for caching the parsed markdown. If not
   *   set, one will be generated automatically based on the provided $url.
   * @param \Drupal\Core\Language\LanguageInterface $language
   *   Optional. The language of the markdown that is being parsed.
   *
   * @return \Drupal\markdown\ParsedMarkdownInterface
   *   A ParsedMarkdown object.
   *
   * @throws \Drupal\markdown\Exception\MarkdownUrlNotExistsException
   */
  public function loadUrl($url, $id = NULL, LanguageInterface $language = NULL);

  /**
   * Parses markdown into HTML.
   *
   * @param string $markdown
   *   The markdown string to parse.
   * @param \Drupal\Core\Language\LanguageInterface $language
   *   Optional. The language of the markdown that is being parsed.
   *
   * @return \Drupal\markdown\ParsedMarkdownInterface
   *   A ParsedMarkdown object.
   */
  public function parse($markdown, LanguageInterface $language = NULL);

  /**
   * Retrieves a MarkdownParser plugin.
   *
   * @param string $parser
   *   Optional. The plugin identifier of a specific MarkdownParser to retrieve.
   *   If not provided, the global parser will be used.
   * @param array $configuration
   *   An array of configuration relevant to the plugin instance.
   *
   * @return \Drupal\markdown\Plugin\Markdown\MarkdownParserInterface
   *   A MarkdownParser plugin.
   */
  public function getParser($parser = NULL, array $configuration = []);

  /**
   * Saves a parsed markdown object.
   *
   * @param string $id
   *   The identifier to use when saving the parsed markdown object.
   * @param \Drupal\markdown\ParsedMarkdownInterface $parsed
   *   The parsed markdown object to save.
   *
   * @return \Drupal\markdown\ParsedMarkdownInterface
   *   The passed parsed markdown.
   */
  public function save($id, ParsedMarkdownInterface $parsed);

}
