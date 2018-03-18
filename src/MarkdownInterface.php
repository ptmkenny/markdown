<?php

namespace Drupal\markdown;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\filter\Plugin\FilterInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;

/**
 * Interface MarkdownInterface.
 */
interface MarkdownInterface extends ContainerAwareInterface, ContainerInjectionInterface {

  /**
   * Retrieves a specific MarkdownParser.
   *
   * @param string $parser
   *   The plugin identifier of the MarkdownParser to retrieve. If not provided,
   *   the first enabled Markdown filter in a text formatter available to the
   *   current user is used.
   *
   * @return \Drupal\markdown\Plugin\Markdown\MarkdownParserInterface
   *   A MarkdownParser plugin.
   */
  public function getParser($parser = NULL);

  /**
   * Retrieves a MarkdownParser plugin from a Filter plugin.
   *
   * @param \Drupal\filter\Plugin\FilterInterface $filter
   *   Optional A filter plugin to use.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   Optional. An account used to retrieve filters available filters if one
   *   wasn't already specified.
   *
   * @return \Drupal\markdown\Plugin\Markdown\MarkdownParserInterface
   *   A MarkdownParser plugin.
   */
  public function getParserFromFilter(FilterInterface $filter = NULL, AccountInterface $account = NULL);

  /**
   * Retrieves a MarkdownParser plugin from a FilterFormat entity.
   *
   * @param \Drupal\filter\FilterFormatInterface|string $filter_format
   *   A FilterFormat entity or identifier to use.
   *
   * @return \Drupal\markdown\Plugin\Markdown\MarkdownParserInterface
   *   A MarkdownParser plugin.
   */
  public function getParserFromFilterFormat($filter_format);

  /**
   * Parses markdown into HTML.
   *
   * @param string $markdown
   *   The markdown string to parse.
   * @param \Drupal\Core\Language\LanguageInterface $language
   *   Optional. The language of the text that is being converted.
   * @param \Drupal\filter\Plugin\FilterInterface $filter
   *   Optional A filter plugin to use.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   Optional. An account used to retrieve filters available filters if one
   *   wasn't already specified.
   *
   * @return string
   *   The converted markup.
   */
  public function parse($markdown, LanguageInterface $language = NULL, FilterInterface $filter = NULL, AccountInterface $account = NULL);

}
