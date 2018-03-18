<?php

namespace Drupal\markdown;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\filter\FilterFormatInterface;
use Drupal\filter\Plugin\FilterInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class MarkdownConverter.
 */
class Markdown implements ContainerAwareInterface, ContainerInjectionInterface, MarkdownInterface {

  use ContainerAwareTrait;
  use StringTranslationTrait;

  /**
   * The MarkdownParser Plugin Manager.
   *
   * @var \Drupal\markdown\MarkdownParserPluginManager
   */
  protected $parsers;

  /**
   * Markdown constructor.
   *
   * @param \Drupal\markdown\MarkdownParserPluginManager $markdown_parsers
   *   The MarkdownParser Plugin Manager service.
   */
  public function __construct(MarkdownParserPluginManager $markdown_parsers) {
    $this->parsers = $markdown_parsers;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container = NULL) {
    if (!isset($container)) {
      $container = \Drupal::getContainer();
    }
    return new static(
      $container->get('plugin.manager.markdown.parser')
    );
  }

  /**
   * Retrieves a specific MarkdownParser.
   *
   * @param string $parser
   *   The plugin identifier of the MarkdownParser to retrieve. If not provided,
   *   the first enabled Markdown filter in a text formatter available to the
   *   current user is used.
   *
   * @return \Drupal\markdown\Plugin\Markdown\MarkdownParserInterface
   *   A MarkdownParser plugin.
   */
  public function getParser($parser = NULL) {
    return $this->parsers->createInstance($parser);
  }

  /**
   * Retrieves a MarkdownParser plugin from a Filter plugin.
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
  public function getParserFromFilter(FilterInterface $filter = NULL, AccountInterface $account = NULL) {
    return $this->parsers->createInstance('', [
      'filter' => $filter,
      'account' => $account,
    ]);
  }

  /**
   * Retrieves a MarkdownParser plugin from a FilterFormat entity.
   *
   * @param \Drupal\filter\FilterFormatInterface|string $filter_format
   *   A FilterFormat entity or identifier to use.
   *
   * @return \Drupal\markdown\Plugin\Markdown\MarkdownParserInterface
   *   A MarkdownParser plugin.
   */
  public function getParserFromFilterFormat($filter_format) {
    if (is_string($filter_format)) {
      /* @noinspection PhpUnhandledExceptionInspection */
      $filter_format = \Drupal::entityTypeManager()
        ->getStorage('filter_format')
        ->load($filter_format);
    }
    if (!($filter_format instanceof FilterFormatInterface)) {
      throw new \InvalidArgumentException($this->t('Invalid filter format specified: @filter_format', ['@filter_format' => (string) $filter_format]));
    }
    return $this->getParserFromFilter($filter_format->filters()
      ->get('markdown'));
  }

  /**
   * Parses markdown into HTML.
   *
   * @param string $markdown
   *   The markdown string to parse.
   * @param \Drupal\Core\Language\LanguageInterface $language
   *   Optional. The language of the text that is being converted.
   * @param \Drupal\filter\Plugin\FilterInterface $filter
   *   Optional A filter plugin to use.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   Optional. An account used to retrieve filters available filters if one
   *   wasn't already specified.
   *
   * @return string
   *   The converted markup.
   */
  public function parse($markdown, LanguageInterface $language = NULL, FilterInterface $filter = NULL, AccountInterface $account = NULL) {
    return $this->getParserFromFilter($filter, $account)
      ->parse($markdown, $language);
  }

}
