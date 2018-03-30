<?php

namespace Drupal\markdown;

use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\filter\FilterFormatInterface;
use Drupal\filter\Plugin\FilterInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class Markdown.
 */
class Markdown implements MarkdownInterface {

  use ContainerAwareTrait;
  use StringTranslationTrait;

  /**
   * The MarkdownParser Plugin Manager.
   *
   * @var \Drupal\markdown\MarkdownParsersInterface
   */
  protected $parsers;

  /**
   * Markdown constructor.
   *
   * @param \Drupal\markdown\MarkdownParsersInterface $markdown_parsers
   *   The MarkdownParser Plugin Manager service.
   */
  public function __construct(MarkdownParsersInterface $markdown_parsers) {
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
   * {@inheritdoc}
   */
  public function getParser($parser = NULL) {
    return $this->parsers->createInstance($parser);
  }

  /**
   * {@inheritdoc}
   */
  public function getParserFromFilter(FilterInterface $filter = NULL, AccountInterface $account = NULL) {
    return $this->parsers->createInstance('', [
      'filter' => $filter,
      'account' => $account,
    ]);
  }

  /**
   * {@inheritdoc}
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
   * {@inheritdoc}
   */
  public function parse($markdown, LanguageInterface $language = NULL, FilterInterface $filter = NULL, AccountInterface $account = NULL) {
    return $this->getParserFromFilter($filter, $account)->parse($markdown, $language);
  }

  /**
   * {@inheritdoc}
   */
  public function render($markdown, LanguageInterface $language = NULL, FilterInterface $filter = NULL, AccountInterface $account = NULL) {
    return $this->getParserFromFilter($filter, $account)->render($markdown, $language);
  }

}
