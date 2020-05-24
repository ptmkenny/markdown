<?php

namespace Drupal\markdown\Plugin\Markdown;

/**
 * Interface for installable Markdown plugins.
 */
interface InstallablePluginInterface extends PluginInterface {

  /**
   * Retrieves the deprecation message, if any.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup|null
   */
  public function getDeprecated();

  /**
   * Retrieves the installed class.
   *
   * @return string
   *   The installed class name.
   */
  public function getInstalledClass();

  /**
   * Retrieves the installation instructions if the plugin is not installed.
   *
   * @return \Drupal\Component\Render\MarkupInterface
   */
  public function getInstallationInstructions();

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
   * Retrieves the merged preferred install plugin definition.
   *
   * @return array
   *   The merged preferred install definition.
   */
  public function getPreferredInstallDefinition();

  /**
   * The current version of the parser.
   *
   * @return string|null
   *   The parser version.
   */
  public function getVersion();

  /**
   * Instantiates a new instance of the installed class.
   *
   * @param mixed $args
   *   An array of arguments.
   * @param mixed $_
   *   Additional arguments.
   *
   * @return mixed
   *   A newly instantiated class.
   *
   * @TODO: Refactor to use variadic parameters.
   */
  public function instantiateInstalledClass($args = NULL, $_ = NULL);

  /**
   * Indicates whether plugin has multiple installs to check.
   *
   * @return bool
   *   TRUE or FALSE
   */
  public function hasMultipleInstalls();

  /**
   * Indicates whether the parser is installed.
   *
   * @return bool
   *   TRUE or FALSE
   */
  public function isInstalled();

  /**
   * Indicates whether plugin is using the first defined install.
   *
   * @return bool
   *   TRUE or FALSE
   */
  public function isPreferredInstall();

}
