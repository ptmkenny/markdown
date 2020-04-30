<?php

namespace Drupal\markdown\Plugin\Markdown;

use Drupal\Core\Form\SubformStateInterface;
use Drupal\Core\Language\LanguageInterface;

/**
 * @MarkdownParser(
 *   id = "michelf/php-markdown",
 *   label = @Translation("PHP Markdown"),
 *   url = "https://michelf.ca/projects/php-markdown",
 *   weight = 31,
 * )
 */
class PhpMarkdown extends MarkdownParserBase implements MarkdownPluginSettingsInterface {

  /**
   * The PHP Markdown class to use.
   *
   * @var string
   */
  protected static $phpMarkdownClass = '\\Michelf\\Markdown';

  /**
   * The PHP Markdown instance.
   *
   * @var \Michelf\Markdown
   */
  protected $phpMarkdown;

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'code_attr_on_pre' => FALSE,
      'code_class_prefix' => '',
      'empty_element_suffix' => ' />',
      'enhanced_ordered_list' => TRUE,
      'fn_backlink_class' => 'footnote-backref',
      'fn_backlink_html' => '&#8617;&#xFE0E;',
      'fn_backlink_title' => '',
      'fn_id_prefix' => '',
      'fn_link_class' => 'footnote-ref',
      'fn_link_title' => '',
      'hard_wrap' => FALSE,
      'no_entities' => FALSE,
      'no_markup' => FALSE,
      'predef_titles' => [],
      'predef_urls' => [],
      'tab_width' => 4,
      'table_align_class_tmpl' => '',
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public static function installed() {
    return class_exists(static::$phpMarkdownClass);
  }

  /**
   * {@inheritdoc}
   */
  public static function version() {
    if (static::installed()) {
      $class = static::$phpMarkdownClass;
      return $class::MARKDOWNLIB_VERSION;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function buildSettingsForm(array $element, SubformStateInterface $form_state) {
    $element = parent::buildSettingsForm($element, $form_state);

    $element['todo']['#markup'] = 'TODO: Implement \Drupal\markdown\Plugin\Markdown\PhpMarkdown::buildSettingsForm() method.';
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function convertToHtml($markdown, LanguageInterface $language = NULL) {
    return $this->phpMarkdown()->transform($markdown);
  }

  /**
   * Retrieves the PHP Markdown parser.
   *
   * @return \Michelf\Markdown
   *   A PHP Markdown parser.
   */
  protected function phpMarkdown() {
    if (!$this->phpMarkdown) {
      $this->phpMarkdown = new static::$phpMarkdownClass();
      foreach ($this->getSettings() as $name => $value) {
        $this->phpMarkdown->$name = $value;
      }
    }
    return $this->phpMarkdown;
  }

}
