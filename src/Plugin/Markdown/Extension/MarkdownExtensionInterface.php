<?php

namespace Drupal\markdown\Plugin\Markdown\Extension;

use Drupal\Core\Form\FormStateInterface;
use Drupal\markdown\Plugin\Markdown\MarkdownInstallablePluginInterface;
use Drupal\markdown\Plugin\Markdown\MarkdownPluginSettingsInterface;

/**
 * Interface ExtensionInterface.
 */
interface MarkdownExtensionInterface extends MarkdownInstallablePluginInterface, MarkdownPluginSettingsInterface {

  /**
   * Indicates whether the extension is being used.
   *
   * @return bool
   *   TRUE or FALSE
   */
  public function isEnabled();

}
