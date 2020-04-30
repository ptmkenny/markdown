<?php

namespace Drupal\markdown\Plugin\Markdown\Extension;

use Drupal\markdown\Plugin\Markdown\MarkdownInstallablePluginInterface;

/**
 * Interface ExtensionInterface.
 */
interface MarkdownExtensionInterface extends MarkdownInstallablePluginInterface {

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
   */
  public function requires();

  /**
   * Retrieves identifiers of extensions that are required by this extension.
   *
   * @return string[]
   */
  public function requiredBy();

}
