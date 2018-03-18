<?php

namespace Drupal\markdown;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\markdown\Annotation\MarkdownExtension;
use Drupal\markdown\Plugin\Markdown\Extension\MarkdownExtensionInterface;
use Drupal\markdown\Plugin\Markdown\MarkdownParserInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class MarkdownExtensions.
 */
class MarkdownExtensions extends DefaultPluginManager implements MarkdownExtensionsInterface {

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
   * Retrieves MarkdownExtensions.
   *
   * @param string $parser
   *   Optional. A specific parser's extensions to retrieve. If not set, all
   *   extensions are returned, regardless of the parser.
   *
   * @return \Drupal\markdown\Plugin\Markdown\Extension\MarkdownExtensionInterface[]
   *   An array of MarkdownExtension plugins.
   */
  public function getExtensions($parser = NULL) {
    // Normalize parser to a string representation of its plugin identifier.
    if ($parser instanceof MarkdownParserInterface) {
      $parser = $parser->getPluginId();
    }

    $extensions = [];
    foreach ($this->getDefinitions() as $plugin_id => $definition) {
      // Skip extensions that don't belong to a particular parser.
      if (isset($parser) && (!isset($definition['parser']) || $definition['parser'] !== $parser)) {
        continue;
      }
      $extensions[$plugin_id] = $this->createInstance($plugin_id);
    }
    return $extensions;
  }

}
