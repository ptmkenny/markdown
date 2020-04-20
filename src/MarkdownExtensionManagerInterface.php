<?php

namespace Drupal\markdown;

use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;

/**
 * Interface MarkdownExtensionsInterface.
 *
 * @method \Drupal\markdown\Plugin\Markdown\Extension\MarkdownExtensionInterface[] all($includeBroken = FALSE) : array
 * @method \Drupal\markdown\Plugin\Markdown\Extension\MarkdownExtensionInterface[] getInstalled(array $configuration = []) : array
 * @method \Drupal\markdown\Plugin\Markdown\Extension\MarkdownExtensionInterface createInstance($plugin_id, array $configuration = [])
 */
interface MarkdownExtensionManagerInterface extends MarkdownPluginManagerInterface {

  /**
   * Retrieves MarkdownExtension plugins.
   *
   * @param string $parser
   *   Optional. A specific parser's extensions to retrieve. If not set, all
   *   available extensions are returned, regardless of the parser.
   * @param bool $enabled
   *   Flag indicating whether to filter results based on enabled status. By
   *   default, all extensions are returned. If set to TRUE, only enabled
   *   extensions are returned. If set to FALSE, only disabled extensions are
   *   returned.
   *
   * @return \Drupal\markdown\Plugin\Markdown\Extension\MarkdownExtensionInterface[]
   *   An array of MarkdownExtension plugins.
   */
  public function getExtensions($parser = NULL, $enabled = NULL);

}
