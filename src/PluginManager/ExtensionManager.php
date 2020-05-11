<?php

namespace Drupal\markdown\PluginManager;

use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\markdown\Annotation\MarkdownExtension;
use Drupal\markdown\Plugin\Markdown\ExtensionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Markdown Extension Plugin Manager.
 *
 * @method mixed[] getDefinitions($includeBroken = TRUE)
 * @method \Drupal\markdown\Plugin\Markdown\ExtensionInterface[] all(array $configuration = [], $includeBroken = FALSE) : array
 * @method \Drupal\markdown\Plugin\Markdown\ExtensionInterface[] installed(array $configuration = []) : array
 * @method \Drupal\markdown\Plugin\Markdown\ExtensionInterface createInstance($plugin_id, array $configuration = [])
 * @noinspection PhpUnnecessaryFullyQualifiedNameInspection
 */
class ExtensionManager extends BasePluginManager implements ExtensionManagerInterface {

  /**
   * {@inheritdoc}
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    parent::__construct('Plugin/Markdown', $namespaces, $module_handler, ExtensionInterface::class, MarkdownExtension::class);
    $this->setCacheBackend($cache_backend, 'markdown_extension_info');
    $this->alterInfo($this->cacheKey);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = new static(
      $container->get('container.namespaces'),
      $container->get('cache.discovery'),
      $container->get('module_handler')
    );
    $instance->setContainer($container);
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  protected function alterDefinitions(&$definitions) {
    // Create dependency relationships between extensions.
    foreach (array_keys($definitions) as $plugin_id) {
      if (!isset($definitions[$plugin_id]['requiredBy'])) {
        $definitions[$plugin_id]['requiredBy'] = [];
      }
      if (!empty($definitions[$plugin_id]['requires'])) {
        foreach ($definitions[$plugin_id]['requires'] as $key => $requirement) {
          // Check that the plugin exists.
          if (!isset($definitions[$requirement])) {
            throw new PluginNotFoundException($requirement);
          }
          // Extensions cannot require themselves.
          if ($requirement === $plugin_id) {
            throw new InvalidPluginDefinitionException($plugin_id, 'Extensions cannot require themselves.');
          }
          if (!isset($definitions[$requirement]['requiredBy'])) {
            $definitions[$requirement]['requiredBy'] = [];
          }
          if (!in_array($requirement, $definitions[$requirement]['requiredBy'])) {
            $definitions[$requirement]['requiredBy'][] = $plugin_id;
          }
        }
      }
    }
    parent::alterDefinitions($definitions);
  }

  /**
   * {@inheritdoc}
   */
  public function getFallbackPluginId($plugin_id = NULL, array $configuration = []) {
    return '_missing_parser';
  }

}
