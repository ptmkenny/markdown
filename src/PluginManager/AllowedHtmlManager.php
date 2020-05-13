<?php

namespace Drupal\markdown\PluginManager;

use Drupal\Component\Plugin\Exception\PluginException;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Extension\ThemeHandlerInterface;
use Drupal\Core\Theme\ActiveTheme;
use Drupal\Core\Theme\ThemeManagerInterface;
use Drupal\filter\Entity\FilterFormat;
use Drupal\filter\FilterPluginManager;
use Drupal\markdown\Annotation\MarkdownAllowedHtml;
use Drupal\markdown\Plugin\Markdown\AllowedHtmlInterface;
use Drupal\markdown\Plugin\Markdown\ExtensibleParserInterface;
use Drupal\markdown\Plugin\Markdown\ExtensionInterface;
use Drupal\markdown\Plugin\Markdown\ParserInterface;
use Drupal\markdown\Util\FilterAwareInterface;
use Drupal\markdown\Util\FilterFormatAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Markdown Allowed HTML Plugin Manager.
 *
 * @method \Drupal\markdown\Plugin\Markdown\AllowedHtmlInterface createInstance($plugin_id, array $configuration = [])
 * @noinspection PhpUnnecessaryFullyQualifiedNameInspection
 */
class AllowedHtmlManager extends BasePluginManager {

  /**
   * The Markdown Extension Plugin Manager service.
   *
   * @var \Drupal\markdown\PluginManager\ExtensionManagerInterface
   */
  protected $extensionManager;

  /**
   * The Filter Plugin Manager service.
   *
   * @var \Drupal\filter\FilterPluginManager
   */
  protected $filterManager;

  /**
   * The Markdown Parser Plugin Manager service.
   *
   * @var \Drupal\markdown\PluginManager\ParserManagerInterface
   */
  protected $parserManager;

  /**
   * The Theme Handler service.
   *
   * @var \Drupal\Core\Extension\ThemeHandlerInterface|string
   */
  protected $themeHandler;

  /**
   * The Theme Manager service.
   *
   * @var \Drupal\Core\Theme\ThemeManagerInterface
   */
  protected $themeManager;

  /**
   * {@inheritdoc}
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler, FilterPluginManager $filterManager, ThemeHandlerInterface $themeHandler, ThemeManagerInterface $themeManager, ParserManagerInterface $parserManager, ExtensionManagerInterface $extensionManager) {
    // Add in theme namespaces.
    // @todo Fix when theme namespaces are properly registered.
    // @see https://www.drupal.org/project/drupal/issues/2941757
    $namespaces = iterator_to_array($namespaces);
    foreach ($themeHandler->listInfo() as $extension) {
      $namespaces['Drupal\\' . $extension->getName()] = [DRUPAL_ROOT . '/' . $extension->getPath() . '/src'];
    }
    parent::__construct('Plugin/Markdown', new \ArrayObject($namespaces), $module_handler, AllowedHtmlInterface::class, MarkdownAllowedHtml::class);
    $this->setCacheBackend($cache_backend, 'markdown_allowed_html_info');
    $this->alterInfo($this->cacheKey);
    $this->filterManager = $filterManager;
    $this->themeHandler = $themeHandler;
    $this->themeManager = $themeManager;
    $this->parserManager = $parserManager;
    $this->extensionManager = $extensionManager;
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
      $container->get('module_handler'),
      $container->get('plugin.manager.filter'),
      $container->get('theme_handler'),
      $container->get('theme.manager'),
      $container->get('plugin.manager.markdown.parser'),
      $container->get('plugin.manager.markdown.extension')
    );
    $instance->setContainer($container);
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  protected function alterDefinitions(&$definitions) {
    if ($this->alterHook) {
      $this->moduleHandler->alter($this->alterHook, $definitions);
      $this->themeManager->alter($this->alterHook, $definitions);
    }
  }

  /**
   * Retrieves plugins that apply to a parser and active theme.
   *
   * Note: this is primarily for use when actually parsing markdown.
   *
   * @param \Drupal\markdown\Plugin\Markdown\ParserInterface $parser
   *   A markdown parser.
   * @param \Drupal\Core\Theme\ActiveTheme $activeTheme
   *   Optional. The active them. This is used as an indicator when in
   *   "render mode".
   * @param array $definitions
   *   Optional. Specific plugin definitions.
   *
   * @return \Drupal\markdown\Plugin\Markdown\AllowedHtmlInterface[]
   *   Plugins that apply to the $parser.
   */
  public function appliesTo(ParserInterface $parser, ActiveTheme $activeTheme = NULL, array $definitions = NULL) {
    $instances = [];
    foreach ($this->getGroupedDefinitions($definitions) as $group => $groupDefinitions) {
      // Filter group definitions based on enabled status of the parser when
      // an active theme has been provided.
      if ($activeTheme) {
        $groupDefinitions = array_intersect_key($groupDefinitions, array_filter($parser->getAllowedHtmlPlugins()));
      }

      switch ($group) {
        case 'extension':
          $groupDefinitions = $this->getExtensionDefinitions($parser, $groupDefinitions, $activeTheme);
          break;

        case 'filter':
          $filter = $parser instanceof FilterAwareInterface ? $parser->getFilter() : NULL;
          $filterFormat = $filter instanceof FilterFormatAwareInterface ? $filter->getFilterFormat() : NULL;
          $groupDefinitions = $this->getFilterDefinitions($filterFormat, $groupDefinitions, $activeTheme);
          break;

        case 'parser':
          $groupDefinitions = $this->getParserDefinitions($parser, $groupDefinitions, $activeTheme);
          break;

        case 'theme':
          // If an active theme was provided, then filter out the theme
          // based plugins that are supported by the active theme.
          if ($activeTheme) {
            $groupDefinitions = $this->getThemeDefinitions($groupDefinitions, $activeTheme);
          }
          break;
      }
      foreach (array_keys($groupDefinitions) as $plugin_id) {
        try {
          $instances[$plugin_id] = $this->createInstance($plugin_id, [
            'activeTheme' => $activeTheme,
            'parser' => $parser,
          ]);
        }
        catch (PluginException $e) {
          // Intentionally do nothing.
        }
      }
    }
    return $instances;
  }

  /**
   * {@inheritdoc}
   */
  public function getFallbackPluginId($plugin_id = NULL, array $configuration = []) {
    return $plugin_id;
  }

  /**
   * Retrieves definitions supported by parser extensions.
   *
   * @param \Drupal\markdown\Plugin\Markdown\ParserInterface $parser
   *   A parser.
   * @param array $definitions
   *   Optional. Specific definitions to filter, if not provided then all
   *   plugins with an "extension" type will be filtered.
   * @param \Drupal\Core\Theme\ActiveTheme $activeTheme
   *   Optional. The active them. This is used as an indicator when in
   *   "render mode".
   *
   * @return array
   *   A filtered list of definitions supported by parser extensions.
   */
  public function getExtensionDefinitions(ParserInterface $parser, array $definitions = NULL, ActiveTheme $activeTheme = NULL) {
    // Immediately return if parser isn't extensible.
    if (!($parser instanceof ExtensibleParserInterface)) {
      return [];
    }
    $definitions = isset($definitions) ? $definitions : $this->getType('extension');

    // Extension only applies to parser when it's supported by it.
    foreach ($definitions as $plugin_id => $definition) {
      $class = $this->normalizeClassName($definition['class']);
      foreach ($parser->extensionInterfaces() as $interface) {
        if (is_subclass_of($class, $this->normalizeClassName($interface))) {
          continue 2;
        }
      }
      unset($definitions[$plugin_id]);
    }

    return $definitions;
  }

  /**
   * Retrieves definitions required by filters.
   *
   * @param \Drupal\filter\Entity\FilterFormat $filterFormat
   *   A filter format.
   * @param array $definitions
   *   Optional. Specific definitions to filter, if not provided then all
   *   plugins with a "filter" type will be filtered.
   * @param \Drupal\Core\Theme\ActiveTheme $activeTheme
   *   Optional. The active them. This is used as an indicator when in
   *   "render mode".
   *
   * @return array
   *   A filtered list of definitions supported by the required filter.
   */
  public function getFilterDefinitions(FilterFormat $filterFormat = NULL, array $definitions = NULL, ActiveTheme $activeTheme = NULL) {
    // Immediately return if no filter format was provided.
    if (!$filterFormat) {
      return [];
    }
    $definitions = isset($definitions) ? $definitions : $this->getType('filter');
    $filters = $filterFormat->filters();
    foreach ($definitions as $plugin_id => $definition) {
      // Remove definitions if:
      // 1. Doesn't have "requiresFilter" set.
      // 2. Filter specified by "requiresFilter" doesn't exist.
      // 3. Filter specified by "requiresFilter" isn't actually being used
      //    (status/enabled) during time of render (ActiveTheme).
      if (!isset($definition['requiresFilter']) || !$filters->has($definition['requiresFilter']) || ($activeTheme && !$filters->get($definition['requiresFilter'])->status)) {
        unset($definitions[$plugin_id]);
      }
    }
    return $definitions;
  }

  /**
   * {@inheritdoc}
   */
  public function getGroupedDefinitions(array $definitions = NULL, $label_key = 'label') {
    $definitions = $this->getSortedDefinitions(isset($definitions) ? $definitions : $this->installedDefinitions(), $label_key);
    $grouped_definitions = array();
    foreach ($definitions as $id => $definition) {
      $grouped_definitions[(string) $definition['type']][$id] = $definition;
    }
    return $grouped_definitions;
  }

  /**
   * Retrieves the definition provided by the parser.
   *
   * @param \Drupal\markdown\Plugin\Markdown\ParserInterface $parser
   *   A parser.
   * @param array $definitions
   *   Optional. Specific definitions to filter, if not provided then all
   *   plugins with an "extension" type will be filtered.
   * @param \Drupal\Core\Theme\ActiveTheme $activeTheme
   *   Optional. The active them. This is used as an indicator when in
   *   "render mode".
   *
   * @return array
   *   A filtered list of definitions provided by the parser.
   */
  public function getParserDefinitions(ParserInterface $parser, array $definitions = NULL, ActiveTheme $activeTheme = NULL) {
    $definitions = isset($definitions) ? $definitions : $this->getType('parser');
    $parserClass = $this->normalizeClassName(get_class($parser));
    foreach ($definitions as $plugin_id => $definition) {
      $class = $this->normalizeClassName($definition['class']);
      if ($parserClass !== $class && !is_subclass_of($parser, $class)) {
        unset($definitions[$plugin_id]);
      }
    }
    return $definitions;
  }

  /**
   * {@inheritdoc}
   */
  public function getSortedDefinitions(array $definitions = NULL, $label_key = 'label') {
    // Sort the plugins first by type, then by label.
    $definitions = isset($definitions) ? $definitions : $this->installedDefinitions();
    uasort($definitions, function ($a, $b) use ($label_key) {
      if ($a['type'] != $b['type']) {
        return strnatcasecmp($a['type'], $b['type']);
      }
      return strnatcasecmp($a[$label_key], $b[$label_key]);
    });
    return $definitions;
  }

  /**
   * Retrieves definitions supported by the active theme.
   *
   * @param array $definitions
   *   Optional. Specific definitions to filter, if not provided then all
   *   plugins with a "theme" type will be filtered.
   * @param \Drupal\Core\Theme\ActiveTheme $activeTheme
   *   Optional. The active them. This is used as an indicator when in
   *   "render mode".
   *
   * @return array
   *   A filtered list of definitions supported by the active theme.
   */
  public function getThemeDefinitions(array $definitions = NULL, ActiveTheme $activeTheme = NULL) {
    $definitions = isset($definitions) ? $definitions : $this->getType('theme');

    // Only use definitions found in the active theme or its base theme(s).
    if ($activeTheme) {
      $themeAncestry = array_merge(array_keys($activeTheme->getBaseThemes()), [$activeTheme->getName()]);
      foreach ($definitions as $plugin_id => $definition) {
        if ($this->themeHandler->themeExists($definition['provider']) && !in_array($definition['provider'], $themeAncestry, TRUE)) {
          unset($definitions[$plugin_id]);
        }
      }
    }

    return $definitions;
  }

  /**
   * Retrieves plugins matching a specific type.
   *
   * @param string $type
   *   The type to retrieve.
   * @param array[]|null $definitions
   *   (optional) The plugin definitions to group. If omitted, all plugin
   *   definitions are used.
   *
   * @return array[]
   *   Keys are type names, and values are arrays of which the keys are
   *   plugin IDs and the values are plugin definitions.
   */
  protected function getType($type, array $definitions = NULL) {
    $grouped_definitions = $this->getGroupedDefinitions($definitions);
    return isset($grouped_definitions[$type]) ? $grouped_definitions[$type] : [];
  }

  /**
   * {@inheritdoc}
   */
  protected function getProviderName($provider) {
    if ($this->moduleHandler->moduleExists($provider)) {
      return $this->moduleHandler->getName($provider);
    }
    if ($this->themeHandler->themeExists($provider)) {
      return $this->themeHandler->getName($provider);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function processDefinition(&$definition, $plugin_id) {
    parent::processDefinition($definition, $plugin_id);
    if (!is_array($definition)) {
      return;
    }

    // Immediately return the plugin isn't installed.
    if (!$definition['installed']) {
      return;
    }

    $provider = $definition['provider'];

    if (!isset($definition['type'])) {
      $definition['type'] = 'other';

      // If the plugin requires a filter, then set the "type" as a filter.
      if (isset($definition['requiresFilter'])) {
        if ($this->moduleHandler->moduleExists($provider) && !$this->filterManager->hasDefinition($definition['requiresFilter'])) {
          throw new \RuntimeException(sprintf('The Markdown Allowed HTML plugin "%s" specifies that it requires the filter "%s", but no filter exists in any enabled modules.', $plugin_id, $definition['requiresFilter']));
        }
        $definition['type'] = 'filter';
      }
      // Allow parsers to provide their own allowed HTML.
      elseif (is_subclass_of($definition['class'], ParserInterface::class)) {
        $parserDefinition = $this->parserManager->getDefinitionByClassName($definition['class']);
        if ($parserDefinition) {
          $definition['type'] = 'parser';
          $definition['installed'] = $parserDefinition['installed'];
        }
      }
      // Allow extensions to provide their own allowed HTML.
      elseif (is_subclass_of($definition['class'], ExtensionInterface::class)) {
        $extensionDefinition = $this->extensionManager->getDefinitionByClassName($definition['class']);
        if ($extensionDefinition) {
          $definition['type'] = 'extension';
          $definition['installed'] = $extensionDefinition['installed'];
        }
      }
      // Otherwise, determine the extension type and set it as the "type".
      elseif ($this->moduleHandler->moduleExists($provider)) {
        $definition['type'] = 'module';
      }
      elseif ($this->themeHandler->themeExists($plugin_id)) {
        $definition['type'] = 'theme';
      }
    }

    // Provide a default label if none was provided.
    if (empty($definition['label'])) {
      // Use a filter title if necessary.
      if ($definition['type'] === 'filter' && ($filterDefinition = $this->filterManager->getDefinition($definition['requiresFilter'])) && isset($filterDefinition['title'])) {
        $definition['label'] = $filterDefinition['title'];
      }
      // Use the provider name if plugin identifier is the same.
      elseif ($plugin_id === $provider) {
        $definition['label'] = $this->getProviderName($provider);
      }
      // Otherwise, create a human readable label from plugin identifier,
      // if not an extension.
      elseif ($definition['type'] !== 'extension') {
        $definition['label'] = ucwords(trim(str_replace('_', ' ', $plugin_id)));
      }
    }

    // Prefix label with provider (if not the same).
    if (in_array($definition['type'], ['filter', 'module', 'theme']) && $plugin_id !== $provider) {
      $definition['label'] = $this->getProviderName($provider) . ': ' . $definition['label'];
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function providerExists($provider) {
    return $this->moduleHandler->moduleExists($provider) || $this->themeHandler->themeExists($provider);
  }

}
