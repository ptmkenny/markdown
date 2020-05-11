<?php

namespace Drupal\markdown\Plugin\Markdown;

/**
 * Interface for extensions.
 */
interface ExtensionInterface extends InstallablePluginInterface {

  /**
   * Indicates whether the extension is enabled.
   *
   * @return bool
   *   TRUE or FALSE
   */
  public function isEnabled();

  /**
   * Retrieves identifiers of extensions that this extension requires.
   *
   * @return string[]
   *   An indexed array of extensions this extension requires.
   */
  public function requires();

  /**
   * Retrieves identifiers of extensions that are required by this extension.
   *
   * @return string[]
   *   An indexed array of extension required by this extension.
   */
  public function requiredBy();

}
