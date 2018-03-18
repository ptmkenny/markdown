<?php

namespace Drupal\markdown;

use Drupal\Component\Plugin\FallbackPluginManagerInterface;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\filter\Plugin\FilterInterface;
use Drupal\markdown\Annotation\MarkdownParser;
use Drupal\markdown\Plugin\Filter\MarkdownFilterInterface;
use Drupal\markdown\Plugin\Markdown\MarkdownParserInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class MarkdownParserPluginManager.
 */
class MarkdownParserPluginManager extends DefaultPluginManager implements ContainerAwareInterface, ContainerInjectionInterface, FallbackPluginManagerInterface {

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
   *
   * @return \Drupal\markdown\Plugin\Markdown\MarkdownParserInterface
   *   A MarkdownParser plugin.
   */
  public function createInstance($plugin_id = 'commonmark', array $configuration = []) {
    // Retrieve the filter from the configuration.
    $filter = $this->getFilter($plugin_id, $configuration);

    $plugin_id = $filter ? $filter->getSetting('parser', $plugin_id) : $plugin_id;

    /** @var \Drupal\markdown\Plugin\Markdown\MarkdownParserInterface $parser */
    $parser = parent::createInstance($plugin_id, $configuration);

    return $parser;
  }

  /**
   * Retrieves the a filter plugin instance based on passed configuration.
   *
   * @param string $plugin_id
   *   The ID of the plugin being instantiated.
   * @param array $configuration
   *   An array of configuration relevant to the plugin instance.
   *
   * @return \Drupal\markdown\Plugin\Filter\MarkdownFilterInterface|null
   *   A MarkdownFilter instance or NULL if it could not be determined.
   */
  protected function getFilter($plugin_id = 'commonmark', array &$configuration = []) {
    $filter = isset($configuration['filter']) ? $configuration['filter'] : NULL;
    $account = isset($configuration['account']) ? $configuration['account'] : NULL;
    unset($configuration['account']);

    if ($filter === NULL) {
      if ($account === NULL) {
        $account = \Drupal::currentUser();
      }
      foreach (filter_formats($account) as $format) {
        $format_filter = $format->filters()->get('markdown');

        // Skip formats that don't match the desired parser.
        if ($format_filter->status || !($format_filter instanceof MarkdownFilterInterface) || ($plugin_id && ($format_filter->getSetting('parser') !== $plugin_id))) {
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
   * Retrieves a parser based on a filter and its settings.
   *
   * @param \Drupal\filter\Plugin\FilterInterface $filter
   *   Optional A filter plugin to use.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   Optional. An account used to retrieve filters available filters if one
   *   wasn't already specified.
   *
   * @return \Drupal\markdown\Plugin\Markdown\MarkdownParserInterface
   *   A MarkdownParser plugin.
   */
  public function getParser(FilterInterface $filter = NULL, AccountInterface $account = NULL) {
    return $this->createInstance(NULL, [
      'filter' => $filter,
      'account' => $account,
    ]);
  }

}
