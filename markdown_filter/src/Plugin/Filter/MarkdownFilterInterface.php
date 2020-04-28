<?php

namespace Drupal\markdown_filter\Plugin\Filter;

use Drupal\filter\Plugin\FilterInterface;

/**
 * Interface MarkdownFilterInterface.
 */
interface MarkdownFilterInterface extends FilterInterface {

  /**
   * Retrieves the MarkdownParser plugin for this filter.
   *
   * @return \Drupal\markdown\Plugin\Markdown\MarkdownParserInterface
   *   The MarkdownParser plugin.
   */
  public function getParser();

  /**
   * Retrieves the Markdown Settings for the filter.
   *
   * @return \Drupal\markdown\MarkdownSettingsInterface
   *   The settings.
   */
  public function getSettings();

  /**
   * Indicates whether the filter is enabled or not.
   *
   * @return bool
   *   TRUE or FALSE
   */
  public function isEnabled();

}
