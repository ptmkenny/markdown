<?php

namespace Drupal\markdown\Plugin\Markdown;

interface MarkdownInstallablePluginInterface extends MarkdownPluginInterface {

  /**
   * Indicates whether the parser is installed.
   *
   * @return bool
   */
  public static function installed();

  /**
   * Retrieves the version of the installed parser.
   *
   * @return string|null
   */
  public static function version();

  /**
   * Displays the human-readable label of the plugin.
   *
   * @param bool $version
   *   Flag indicating whether to show the version with the label.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup
   *   The label.
   */
  public function getLabel($version = TRUE);

  /**
   * The current version of the parser.
   *
   * @return string|null
   *   The parser version.
   */
  public function getVersion();

  /**
   * Indicates whether the parser is installed.
   *
   * @return bool
   */
  public function isInstalled();


}
