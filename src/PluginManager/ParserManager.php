<?php

namespace Drupal\markdown\PluginManager;

use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\filter\Plugin\FilterInterface;
use Drupal\markdown\Annotation\MarkdownParser;
use Drupal\markdown\Plugin\Markdown\ExtensibleParserInterface;
use Drupal\markdown\Plugin\Markdown\ExtensionInterface;
use Drupal\markdown\Plugin\Markdown\ParserInterface;
use Drupal\markdown\Util\FilterAwareInterface;
use Drupal\markdown\Util\FilterFormatAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Markdown Parser Plugin Manager.
 *
 * @method \Drupal\markdown\Plugin\Markdown\ParserInterface[] all(array $configuration = [], $includeFallback = FALSE) : array
 * @method \Drupal\markdown\Annotation\MarkdownParser getDefinition($plugin_id, $exception_on_invalid = TRUE)
 * @method \Drupal\markdown\Annotation\MarkdownParser|void getDefinitionByClassName($className)
 * @method \Drupal\markdown\Annotation\MarkdownParser[] getDefinitions($includeFallback = TRUE)
 * @method \Drupal\markdown\Plugin\Markdown\ParserInterface[] installed(array $configuration = []) : array
 * @noinspection PhpUnnecessaryFullyQualifiedNameInspection
 */
class ParserManager extends InstallablePluginManager implements ParserManagerInterface {

  /**
   * {@inheritdoc}
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    parent::__construct('Plugin/Markdown', $namespaces, $module_handler, ParserInterface::class, MarkdownParser::class);
    $this->setCacheBackend($cache_backend, 'markdown_parser_info');
    $this->alterInfo($this->cacheKey);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container = NULL) {
    if (!$container) {
      $container = \Drupal::getContainer();
    }
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
  public function createInstance($plugin_id, array $configuration = []) {
    // Capture filter if it was passed along from FilterMarkdown.
    $filter = isset($configuration['filter']) ? $configuration['filter'] : NULL;
    unset($configuration['filter']);

    // Merge passed configuration onto any existing site-wide configuration.
    if (!($filter instanceof FilterInterface)) {
      $configuration = NestedArray::mergeDeep(\Drupal::config("markdown.parser.$plugin_id")->get(), $configuration);
    }

    /** @var \Drupal\markdown\Plugin\Markdown\ParserInterface $parser */
    $parser = parent::createInstance($plugin_id, $configuration);

    $plugin_id = $parser->getPluginId();

    // If the parser is the fallback parser (missing), then just return it.
    if ($plugin_id === $this->getFallbackPluginId()) {
      return $parser;
    }

    // If a filter is present, handle cacheable dependencies differently.
    if ($filter instanceof FilterInterface) {
      if ($parser instanceof FilterAwareInterface) {
        $parser->setFilter($filter);
      }
      // Add a cacheable dependency on the filter format, if it exists.
      if ($filter instanceof FilterFormatAwareInterface && ($filterFormat = $filter->getFilterFormat())) {
        $parser->addCacheableDependency($filterFormat);
      }
    }
    // Otherwise, add a default cache tag.
    else {
      $parser->addCacheTags(["markdown.parser.$plugin_id"]);
    }

    return $parser;
  }

  /**
   * {@inheritdoc}
   */
  public function getFallbackPluginId($plugin_id = NULL, array $configuration = []) {
    return '_missing_parser';
  }

  /**
   * {@inheritdoc}
   */
  public function processDefinition(&$definition, $plugin_id) {
    parent::processDefinition($definition, $plugin_id);

    if (!($definition instanceof MarkdownParser) || !$definition->isInstalled() || !($class = $definition->getClass())) {
      return;
    }

    // Process extensible parser support.
    if (is_subclass_of($class, ExtensibleParserInterface::class)) {
      if (!$definition->extensionInterfaces) {
        throw new InvalidPluginDefinitionException($plugin_id, sprintf('Markdown parser "%s" implements %s but is missing "extensionInterfaces" in the definition.', $plugin_id, ExtensibleParserInterface::class));
      }
      foreach (array_map('\Drupal\markdown\PluginManager\InstallablePluginManager::normalizeClassName', $definition->extensionInterfaces) as $interface) {
        if ($interface === ExtensionInterface::class) {
          throw new InvalidPluginDefinitionException($plugin_id, sprintf('Markdown parser "%s" cannot specify %s as the extension interface. It must create its own unique interface that extend from it.', $plugin_id, ExtensionInterface::class));
        }
        if (!interface_exists($interface)) {
          throw new InvalidPluginDefinitionException($plugin_id, sprintf('Markdown parser "%s" indicates that it supports the extension interface "%s", but this interface does not exist.', $plugin_id, $interface));
        }
        if (!is_subclass_of($interface, ExtensionInterface::class)) {
          throw new InvalidPluginDefinitionException($plugin_id, sprintf('Markdown parser "%s" indicates that it supports the extension interface "%s", but this interface does not extend %s.', $plugin_id, $interface, ExtensionInterface::class));
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function providerExists($provider) {
    // It's known that plugins provided by this module exist. Explicitly and
    // always return TRUE for this case. This is needed during install when
    // the module is not yet (officially) installed.
    // @see markdown_requirements()
    if ($provider === 'markdown') {
      return TRUE;
    }
    return parent::providerExists($provider);
  }

}
