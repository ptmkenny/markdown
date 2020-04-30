<?php

namespace Drupal\markdown;

use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Component\Plugin\Exception\PluginException;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\markdown\Annotation\MarkdownExtension;
use Drupal\markdown\Plugin\Markdown\Extension\MarkdownExtensionInterface;
use Drupal\markdown\Plugin\Markdown\MarkdownParserInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @method mixed[] getDefinitions($includeBroken = TRUE)
 * @method \Drupal\markdown\Plugin\Markdown\Extension\MarkdownExtensionInterface[] all(array $configuration = [], $includeBroken = FALSE) : array
 * @method \Drupal\markdown\Plugin\Markdown\Extension\MarkdownExtensionInterface[] installed(array $configuration = []) : array
 * @method \Drupal\markdown\Plugin\Markdown\Extension\MarkdownExtensionInterface createInstance($plugin_id, array $configuration = [])
 */
class MarkdownExtensionPluginManager extends MarkdownPluginManagerBase implements MarkdownExtensionPluginManagerInterface {

  /**
   * {@inheritdoc}
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    parent::__construct('Plugin/Markdown/Extension', $namespaces, $module_handler, MarkdownExtensionInterface::class, MarkdownExtension::class);
    $this->setCacheBackend($cache_backend, 'markdown_extensions');
    $this->alterInfo('markdown_extensions');
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
   * Group definitions by supported parsers.
   *
   * @param array $definitions
   *   Optional. Specific definitions to group. If not set, all definitions will
   *   be grouped.
   *
   * @return array
   *   An associative array of arrays where the key is a parser identifier and
   *   its value is an associative array key/value a pairs of extension
   *   definitions.
   */
  protected function groupDefinitions(array $definitions = NULL) {
    $definitions = isset($definitions) ? $definitions : $this->getDefinitions();
    $grouped = [];
    foreach ($definitions as $id => $definition) {
      $parsers = isset($definition['parsers']) ? $definition['parsers'] : [];
      foreach ($parsers as $parserId) {
        $grouped[$parserId][$id] = $definition;
      }
    }
    return $grouped;
  }

  /**
   * Groups definitions based on which parser(s) it belongs to.
   *
   * @param string|\Drupal\markdown\Plugin\Markdown\MarkdownParserInterface $parserId
   *   A parser identifier or instance.
   *
   * @return array
   *   An associative array of definitions, keyed by parser identifier.
   */
  protected function parserDefinitions($parserId = NULL) {
    // Retrieve a
    if (!$parserId) {
      return $this->getDefinitions();
    }

    // Normalize parser to a string representation of its plugin identifier.
    if ($parserId instanceof MarkdownParserInterface) {
      $parserId = $parserId->getPluginId();
    }

    $grouped = $this->groupDefinitions();
    return isset($grouped[$parserId]) ? $grouped[$parserId] : [];
  }

  /**
   * {@inheritdoc}
   */
  public function getParserExtensions($parserId, array $configuration = []) {
    $extensions = [];
    foreach ($this->parserDefinitions($parserId) as $plugin_id => $definition) {
      try {
        $extensionConfiguration = isset($configuration[$plugin_id]) ? $configuration[$plugin_id] : [];
        $extensions[$plugin_id] = $this->createInstance($plugin_id, $extensionConfiguration);
      }
      catch (PluginException $e) {
        // Intentionally left empty.
      }
    }
    return $extensions;
  }

  /**
   * {@inheritdoc}
   */
  public function processDefinition(&$definition, $plugin_id) {
    parent::processDefinition($definition, $plugin_id);
    if (!is_array($definition)) {
      return;
    }
    $definition['parsers'] = isset($definition['parsers']) ? (array) $definition['parsers'] : [];
  }

}
