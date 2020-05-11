<?php

namespace Drupal\markdown\PluginManager;

use Drupal\Component\Plugin\FallbackPluginManagerInterface;
use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;

/**
 * Interface for Markdown Plugin Mangers.
 */
interface MarkdownPluginManagerInterface extends ContainerAwareInterface, ContainerInjectionInterface, PluginManagerInterface, FallbackPluginManagerInterface {

  /**
   * Retrieves all registered plugins.
   *
   * @param array $configuration
   *   The configuration used to create plugin instances.
   * @param bool $includeBroken
   *   Flag indicating whether to include the "_missing_parser" fallback plugin.
   *
   * @return \Drupal\markdown\Plugin\Markdown\PluginInterface[]
   *   An array of installed plugins instances, keyed by plugin identifier.
   */
  public function all(array $configuration = [], $includeBroken = FALSE);

  /**
   * Creates a pre-configured instance of a plugin.
   *
   * @param string $plugin_id
   *   The ID of the plugin being instantiated.
   * @param array $configuration
   *   An array of configuration relevant to the plugin instance.
   *
   * @return \Drupal\markdown\Plugin\Markdown\ParserInterface
   *   A fully configured plugin instance.
   */
  public function createInstance($plugin_id, array $configuration = []);

  /**
   * Retrieves the first installed plugin identifier.
   *
   * @return string
   *   The first installed plugin identifier.
   */
  public function firstInstalledPluginId();

  /**
   * Retrieves a definition by class name.
   *
   * @param string $className
   *   The class name to match.
   *
   * @return array|null
   *   The plugin definition matching the class name or NULL if not found.
   */
  public function getDefinitionByClassName($className);

  /**
   * Gets the definition of all plugins for this type.
   *
   * @param bool $includeBroken
   *   Flag indicating whether to include the "fallback" definition.
   *
   * @return array[]
   *   An array of plugin definitions (empty array if no definitions were
   *   found). Keys are plugin IDs.
   */
  public function getDefinitions($includeBroken = TRUE);

  /**
   * Retrieves all installed plugins.
   *
   * @param array $configuration
   *   The configuration used to create plugin instances.
   *
   * @return \Drupal\markdown\Plugin\Markdown\PluginInterface[]
   *   An array of installed plugins instances, keyed by plugin identifier.
   */
  public function installed(array $configuration = []);

  /**
   * Retrieves installed plugin definitions.
   *
   * @return array[]
   *   An array of plugin definitions, keyed by identifier.
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

}
