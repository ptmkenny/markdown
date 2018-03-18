<?php

namespace Drupal\markdown;

use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;

/**
 * Interface MarkdownExtensionsInterface.
 *
 * @method \Drupal\markdown\Plugin\Markdown\Extension\MarkdownExtensionInterface createInstance($plugin_id, array $configuration = [])
 */
interface MarkdownExtensionsInterface extends ContainerAwareInterface, ContainerInjectionInterface, PluginManagerInterface {

  /**
   * Retrieves MarkdownExtensions.
   *
   * @param string $parser
   *   Optional. A specific parser's extensions to retrieve. If not set, all
   *   extensions are returned, regardless of the parser.
   *
   * @return \Drupal\markdown\Plugin\Markdown\Extension\MarkdownExtensionInterface[]
   *   An array of MarkdownExtension plugins.
   */
  public function getExtensions($parser = NULL);

}
