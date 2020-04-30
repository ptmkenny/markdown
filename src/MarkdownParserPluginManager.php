<?php

namespace Drupal\markdown;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\filter\FilterFormatInterface;
use Drupal\markdown\Annotation\MarkdownParser;
use Drupal\markdown\Plugin\Markdown\MarkdownParserInterface;
use Drupal\markdown_filter\Plugin\Filter\MarkdownFilterInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @method \Drupal\markdown\Plugin\Markdown\MarkdownParserInterface[] all(array $configuration = [], $includeBroken = FALSE) : array
 * @method \Drupal\markdown\Plugin\Markdown\MarkdownParserInterface[] installed(array $configuration = []) : array
 * @method \Drupal\markdown\Plugin\Markdown\MarkdownParserInterface createInstance($plugin_id, array $configuration = [])
 */
class MarkdownParserPluginManager extends MarkdownPluginManagerBase implements MarkdownParserPluginManagerInterface {

  /**
   * {@inheritdoc}
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    parent::__construct('Plugin/Markdown', $namespaces, $module_handler, MarkdownParserInterface::class, MarkdownParser::class);
    $this->setCacheBackend($cache_backend, 'markdown_parsers');
    $this->alterInfo('markdown_parsers');
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
  public function getFilter($parser = NULL, array &$configuration = []) {
    global $user;

    $parser = $this->getFallbackPluginId($parser, $configuration);

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
          /** @var \stdClass $format */
          $filters = filter_list_format($format->format);
          if (isset($filters['markdown'])) {
            $format_filter = \Drupal::service('plugin.manager.filter')->createInstance('markdown', (array) $filters['markdown']);
          }
        }
        // Drupal 8.
        else {
          $filters = $format->filters();
          $format_filter = $filters->has('markdown') ? $filters->get('markdown') : NULL;
        }

        // Skip formats that don't match the desired parser.
        if (!$format_filter || $format_filter->status || !($format_filter instanceof MarkdownFilterInterface) || !$format_filter->isEnabled() || ($parser && ($format_filter->getSettings()->getParserId(FALSE) !== $parser))) {
          continue;
        }

        $filter = $format_filter;
        break;
      }
    }
    elseif (is_string($filter)) {
      if ($account === NULL) {
        $account = (int) \Drupal::VERSION[0] >= 8 ? \Drupal::currentUser() : $user;
      }
      $formats = filter_formats($account);
      if (isset($formats[$filter])) {
        $filter = $formats[$filter]->filters()->get('markdown');
      }
      else {
        $filter = NULL;
      }
    }
    elseif ($filter instanceof FilterFormatInterface) {
      $filter = $filter->filters()->get('markdown');
    }

    if ($filter && !($filter instanceof MarkdownFilterInterface)) {
      throw new \InvalidArgumentException(sprintf('Filter provided in configuration must be an instance of %s.', MarkdownFilterInterface::class));
    }

    // Now reset the filter.
    $configuration['filter'] = $filter;

    return $filter;
  }

  /**
   * {@inheritdoc}
   */
  public function getParser($filter = NULL, AccountInterface $account = NULL) {
    return $this->createInstance(NULL, [
      'filter' => $filter,
      'account' => $account,
    ]);
  }

}
