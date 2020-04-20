<?php

namespace Drupal\markdown;

use Drupal\Component\Plugin\Exception\PluginException;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\markdown\Annotation\MarkdownExtension;
use Drupal\markdown\Plugin\Markdown\Extension\MarkdownExtensionInterface;
use Drupal\markdown\Plugin\Markdown\MarkdownParserInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

class MarkdownExtensionManager extends BaseMarkdownPluginManager implements MarkdownExtensionManagerInterface {

  use ContainerAwareTrait;

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
  protected function alterDefinitions(&$definitions) {
    // Remove any plugins that don't actually have the parser installed.
    foreach ($definitions as $plugin_id => $definition) {
      if ($plugin_id === '_broken' || empty($definition['checkClass'])) {
        continue;
      }
      if (!class_exists($definition['checkClass'])) {
        unset($definitions[$plugin_id]);
      }
    }
    parent::alterDefinitions($definitions);
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
  public function getExtensions($parser = NULL, $enabled = NULL) {
    // Normalize parser to a string representation of its plugin identifier.
    if ($parser instanceof MarkdownParserInterface) {
      $parser = $parser->getPluginId();
    }

    $extensions = [];
    foreach ($this->getDefinitions() as $plugin_id => $definition) {
      // Skip extensions that don't belong to a particular parser.
      if ($plugin_id === $this->getFallbackPluginId() || (isset($parser) && $definition['parsers'] && !in_array($parser, $definition['parsers'], TRUE))) {
        continue;
      }
      try {
        $extension = $this->createInstance($plugin_id);

        // Set settings from the definition (i.e. added via alter).
        if (isset($definition['settings'])) {
          $extension->setSettings($definition['settings']);
        }

        if ($enabled === TRUE && $extension->isEnabled()) {
          $extensions[$plugin_id] = $extension;
        }
        elseif ($enabled === FALSE && !$extension->isEnabled()) {
          $extensions[$plugin_id] = $extension;
        }
        elseif ($enabled === NULL) {
          $extensions[$plugin_id] = $extension;
        }
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
    $definition['parsers'] = (array) $definition['parsers'];
  }

}
