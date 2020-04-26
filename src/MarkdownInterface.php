<?php

namespace Drupal\markdown;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;

/**
 * Interface MarkdownInterface.
 */
interface MarkdownInterface extends ContainerAwareInterface, ContainerInjectionInterface {

  /**
   * Loads a cached ParsedMarkdown object.
   *
   * @param string $id
   *   A unique identifier that will be used to cache the parsed markdown.
   */
  public function load($id);

  /**
   * Loads a cached ParsedMarkdown object.
   *
   * @param string $id
   *   A unique identifier that will be used to cache the parsed markdown.
   * @param string $path
   *   The local file system path of a markdown file to parse if the cached
   *   ParsedMarkdown object doesn't yet exist. Once parsed, its identifier
   *   will be set to the provided $id and then cached.
   * @param string $parser
   *   Optional. The plugin identifier of the MarkdownParser to retrieve. If
   *   not provided, the first enabled Markdown filter in a text formatter
   *   available to the current user is used.
   * @param string|\Drupal\filter\Plugin\FilterInterface|\Drupal\filter\FilterFormatInterface $filter
   *   Optional A specific filter plugin to use, a string representing a filter
   *   format or a FilterFormatInterface object containing a "markdown" filter.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   Optional. An account used to retrieve filters available filters if one
   *   wasn't already specified.
   * @param \Drupal\Core\Language\LanguageInterface $language
   *   Optional. The language of the text that is being converted.
   *
   * @return \Drupal\markdown\ParsedMarkdownInterface
   *   A ParsedMarkdown object.
   *
   * @throws \Symfony\Component\HttpFoundation\File\Exception\FileNotFoundException
   *   When the provided $path does not exist in the local file system.
   */
  public function loadPath($id, $path, $parser = NULL, $filter = NULL, AccountInterface $account = NULL, LanguageInterface $language = NULL);

  /**
   * Loads a cached ParsedMarkdown object.
   *
   * @param string $id
   *   A unique identifier that will be used to cache the parsed markdown.
   * @param string $url
   *   The external URL of a markdown file to parse if the cached
   *   ParsedMarkdown object doesn't yet exist. Once parsed, its identifier
   *   will be set to the provided $id and then cached.
   * @param string $parser
   *   Optional. The plugin identifier of the MarkdownParser to retrieve. If
   *   not provided, the first enabled Markdown filter in a text formatter
   *   available to the current user is used.
   * @param string|\Drupal\filter\Plugin\FilterInterface|\Drupal\filter\FilterFormatInterface $filter
   *   Optional A specific filter plugin to use, a string representing a filter
   *   format or a FilterFormatInterface object containing a "markdown" filter.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   Optional. An account used to retrieve filters available filters if one
   *   wasn't already specified.
   * @param \Drupal\Core\Language\LanguageInterface $language
   *   Optional. The language of the text that is being converted.
   *
   * @return \Drupal\markdown\ParsedMarkdownInterface
   *   A ParsedMarkdown object.
   *
   * @throws \Symfony\Component\HttpFoundation\File\Exception\FileNotFoundException
   *   When the provided $url does not exist or is not reachable.
   */
  public function loadUrl($id, $url, $parser = NULL, $filter = NULL, AccountInterface $account = NULL, LanguageInterface $language = NULL);

  /**
   * Parses markdown into HTML.
   *
   * @param string $markdown
   *   The markdown string to parse.
   * @param string $parser
   *   The plugin identifier of the MarkdownParser to retrieve. If not provided,
   *   the first enabled Markdown filter in a text formatter available to the
   *   current user is used.
   * @param string|\Drupal\filter\Plugin\FilterInterface|\Drupal\filter\FilterFormatInterface $filter
   *   Optional A specific filter plugin to use, a string representing a filter
   *   format or a FilterFormatInterface object containing a "markdown" filter.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   Optional. An account used to retrieve filters available filters if one
   *   wasn't already specified.
   * @param \Drupal\Core\Language\LanguageInterface $language
   *   Optional. The language of the text that is being converted.
   *
   * @return \Drupal\markdown\ParsedMarkdownInterface
   *   A ParsedMarkdown object.
   */
  public function parse($markdown, $parser = NULL, $filter = NULL, AccountInterface $account = NULL, LanguageInterface $language = NULL);

  /**
   * Retrieves a specific MarkdownParser.
   *
   * @param string $parser
   *   The plugin identifier of the MarkdownParser to retrieve. If not provided,
   *   the first enabled Markdown filter in a text formatter available to the
   *   current user is used.
   * @param string|\Drupal\filter\Plugin\FilterInterface|\Drupal\filter\FilterFormatInterface $filter
   *   Optional A specific filter plugin to use, a string representing a filter
   *   format or a FilterFormatInterface object containing a "markdown" filter.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   Optional. An account used to retrieve filters available filters if one
   *   wasn't already specified.
   *
   * @return \Drupal\markdown\Plugin\Markdown\MarkdownParserInterface
   *   A MarkdownParser plugin.
   */
  public function getParser($parser = NULL, $filter = NULL, AccountInterface $account = NULL);

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
