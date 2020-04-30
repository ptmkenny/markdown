<?php

namespace Drupal\markdown\Plugin\Markdown;

use Drupal\Core\Language\LanguageInterface;
use Drupal\markdown\ParsedMarkdown;

/**
 * @MarkdownParser(
 *   id = "_broken",
 *   label = @Translation("Missing Parser"),
 * )
 */
class Broken extends MarkdownPluginBase implements MarkdownParserInterface {

  /**
   * {@inheritdoc}
   */
  public static function installed() {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public static function version() {
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  protected function getConfigType() {
    return 'markdown_parser';
  }

  /**
   * {@inheritdoc}
   */
  public function getLabel($version = TRUE) {
    return parent::getLabel();
  }

  /**
   * {@inheritdoc}
   */
  public function getVersion() {
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function isInstalled() {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function convertToHtml($markdown, LanguageInterface $language = NULL) {
    return $markdown;
  }

  /**
   * {@inheritdoc}
   */
  public function getSummary() {
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function parse($markdown, LanguageInterface $language = NULL) {
    return ParsedMarkdown::create($markdown, $markdown, $language);
  }

}
