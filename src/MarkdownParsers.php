<?php

namespace Drupal\markdown;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\filter\Plugin\FilterInterface;
use Drupal\markdown\Annotation\MarkdownParser;
use Drupal\markdown\Plugin\Filter\MarkdownFilterInterface;
use Drupal\markdown\Plugin\Markdown\MarkdownParserInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class MarkdownParsers.
 */
class MarkdownParsers extends DefaultPluginManager implements MarkdownParsersInterface {

  use ContainerAwareTrait;
  use StringTranslationTrait;

  /**
   * The configuration settings for the Markdown module.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $settings;

  /**
   * {@inheritdoc}
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler, ConfigFactoryInterface $config) {
    parent::__construct('Plugin/Markdown', $namespaces, $module_handler, MarkdownParserInterface::class, MarkdownParser::class);
    $this->setCacheBackend($cache_backend, 'markdown_parsers');
    $this->alterInfo('markdown_parsers');
    $this->settings = $config->get('markdown.settings');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = new static(
      $container->get('container.namespaces'),
      $container->get('cache.discovery'),
      $container->get('module_handler'),
      $container->get('config.factory')
    );
    $instance->setContainer($container);
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  protected function alterDefinitions(&$definitions) {
    // Remove any plugins that don't actually have the parser installed.
    foreach ($definitions as $plugin_id => $definition) {
      if ($plugin_id !== '_broken' && $definition['checkClass'] && !class_exists($definition['checkClass'])) {
        unset($definitions[$plugin_id]);
      }
    }
    parent::alterDefinitions($definitions);
  }

  /**
   * {@inheritdoc}
   *
   * @return \Drupal\markdown\Plugin\Markdown\MarkdownParserInterface
   *   A MarkdownParser plugin.
   */
  public function createInstance($plugin_id = 'commonmark', array $configuration = []) {
    // Retrieve the filter from the configuration.
    $filter = $this->getFilter($plugin_id, $configuration);

    $plugin_id = $filter ? $filter->getSetting('parser', $plugin_id) : $plugin_id;

    // Set the settings.
    $configuration['settings'] = NestedArray::mergeDeep($this->settings->get($plugin_id) ?: [], $filter ? $filter->getParserSettings() : []);

    /** @var \Drupal\markdown\Plugin\Markdown\MarkdownParserInterface $parser */
    $parser = parent::createInstance($plugin_id, $configuration);

    return $parser;
  }

  /**
   * {@inheritdoc}
   */
  public function getFilter($parser = 'commonmark', array &$configuration = []) {
    global $user;

    $filter = isset($configuration['filter']) ? $configuration['filter'] : NULL;
    $account = isset($configuration['account']) ? $configuration['account'] : NULL;
    unset($configuration['account']);

    if ($filter === NULL) {
      if ($account === NULL) {
        $account = (int) \Drupal::VERSION[0] >= 8 ? \Drupal::currentUser() : $user;
      }
      foreach (filter_formats($account) as $format) {
        $format_filter = FALSE;

        // Drupal 7.
        if (function_exists('filter_list_format')) {
          $filters = filter_list_format($format->format);
          if (isset($filters['markdown'])) {
            $format_filter = \Drupal::service('plugin.manager.filter')->createInstance('markdown', (array) $filters['markdown']);
          }
        }
        // Drupal 8.
        else {
          $format_filter = $format->filters()->get('markdown');
        }

        // Skip formats that don't match the desired parser.
        if (!$format_filter || $format_filter->status || !($format_filter instanceof MarkdownFilterInterface) || ($parser && ($format_filter->getSetting('parser') !== $parser))) {
          continue;
        }

        $filter = $format_filter;
        break;
      }
    }

    if ($filter && !($filter instanceof MarkdownFilterInterface)) {
      throw new \InvalidArgumentException($this->t('Filter provided in configuration must be an instance of \\Drupal\\markdown\\Plugin\\Filter\\MarkdownFilterInterface.'));
    }

    // Now set the filter.
    $configuration['filter'] = $filter;

    return $filter;
  }

  /**
   * {@inheritdoc}
   */
  public function getFallbackPluginId($plugin_id, array $configuration = []) {
    \Drupal::logger('markdown')
      ->warning($this->t('Unknown MarkdownParser: "@parser".', ['@parser' => $plugin_id]));
    return '_broken';
  }

  /**
   * {@inheritdoc}
   */
  public function getParser(FilterInterface $filter = NULL, AccountInterface $account = NULL) {
    return $this->createInstance(NULL, [
      'filter' => $filter,
      'account' => $account,
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function getParsers($include_broken = FALSE) {
    /** @var \Drupal\markdown\Plugin\Markdown\MarkdownParserInterface[] $parsers */
    $parsers = [];
    foreach (array_keys($this->getDefinitions()) as $plugin_id) {
      if (!$include_broken && $plugin_id === '_broken') {
        continue;
      }
      $parsers[$plugin_id] = $this->createInstance($plugin_id);
    }
    return $parsers;
  }

}
