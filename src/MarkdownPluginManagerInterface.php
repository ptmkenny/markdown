<?php

namespace Drupal\markdown;

use Drupal\Component\Plugin\FallbackPluginManagerInterface;
use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;

/**
 * @method mixed[] getDefinitions($includeBroken = TRUE)
 * @method \Drupal\markdown\Plugin\Markdown\MarkdownPluginInterface createInstance($plugin_id, array $configuration = [])
 */
interface MarkdownPluginManagerInterface extends ContainerAwareInterface, ContainerInjectionInterface, PluginManagerInterface, FallbackPluginManagerInterface {

  /**
   * Retrieves all registered plugins.
   *
   * @param array $configuration
   *   The configuration used to create plugin instances.
   * @param bool $includeBroken
   *   Flag indicating whether to include the "_broken" fallback plugin.
   *
   * @return array
   *   An array of plugins instances, keyed by plugin identifier.
   */
  public function all(array $configuration = [], $includeBroken = FALSE);

  /**
   * Retrieves the first installed plugin identifier.
   *
   * @return string
   *   The first installed plugin identifier.
   */
  public function firstInstalledPluginId();

  /**
   * Retrieves all installed plugins.
   *
   * @param array $configuration
   *   The configuration used to create plugin instances.
   *
   * @return array
   *   An array of installed plugins instances, keyed by plugin identifier.
   */
  public function installed(array $configuration = []);

  /**
   * Retrieves installed plugin definitions.
   *
   * @return array
   */
  public function installedDefinitions();

  /**
   * Retrieves the labels for plugins.
   *
   * @param bool $installed
   *   Flag indicating whether to return just the installed plugins.
   * @param bool $version
   *   Flag indicating whether to include the version with the label.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup[]
   *   An array of labels, keyed by plugin identifier.
   */
  public function labels($installed = TRUE, $version = TRUE);

  /**
   * Sorts a definitions array.
   *
   * This sorts the definitions array first by the weight column, and then by
   * the plugin label, ensuring a stable, deterministic, and testable ordering
   * of plugins.
   *
   * @param array $definitions
   *   The definitions array to sort.
   */
  public function sortDefinitions(array &$definitions);

}
