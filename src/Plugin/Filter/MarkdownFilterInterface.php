<?php

namespace Drupal\markdown\Plugin\Filter;

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
   * Retrieves a specific setting.
   *
   * @param string $name
   *   The name of the setting to retrieve.
   * @param mixed $default
   *   Optional. The default value to return if not set.
   *
   * @return mixed
   *   The setting value.
   */
  public function getSetting($name, $default = NULL);

  /**
   * Indicates whether the filter is enabled or not.
   *
   * @return bool
   *   TRUE or FALSE
   */
  public function isEnabled();

}
