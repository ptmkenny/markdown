<?php

namespace Drupal\markdown;

/**
 * @method mixed[] getDefinitions($includeBroken = TRUE)
 * @method \Drupal\markdown\Plugin\Markdown\Extension\MarkdownExtensionInterface createInstance($plugin_id, array $configuration = [])
 */
interface MarkdownExtensionPluginManagerInterface extends MarkdownPluginManagerInterface {

  /**
   * Retrieves extensions for a specific parser.
   *
   * @param string|\Drupal\markdown\Plugin\Markdown\MarkdownParserInterface $parserId
   *   A parser identifier or MarkdownParser instance.
   * @param array $configuration
   *   The configuration used to create plugin instances. This should be an
   *   associative array, keyed by extension plugin identifiers.
   *
   * @return \Drupal\markdown\Plugin\Markdown\Extension\MarkdownExtensionInterface[]
   *   An array of MarkdownExtension plugins.
   */
  public function getParserExtensions($parserId, array $configuration = []);

}
