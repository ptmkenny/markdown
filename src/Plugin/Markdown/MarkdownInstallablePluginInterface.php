<?php

namespace Drupal\markdown\Plugin\Markdown;

use Drupal\Component\Plugin\PluginInspectionInterface;

interface MarkdownInstallablePluginInterface extends PluginInspectionInterface {

  /**
   * Indicates whether the parser is installed.
   *
   * @return bool
   */
  public static function installed(): bool;

  /**
   * Retrieves the version of the installed parser.
   *
   * @return string|null
   */
  public static function version();

  /**
   * Retrieves the description of the plugin, if set.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup
   *   The description.
   */
  public function getDescription();

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
   * Retrieves the URL of the plugin, if set.
   *
   * @return \Drupal\Core\Url|null
   */
  public function getUrl();

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
  public function isInstalled(): bool;


}
