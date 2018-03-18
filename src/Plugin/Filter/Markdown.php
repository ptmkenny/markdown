<?php

namespace Drupal\markdown\Plugin\Filter;

use Drupal\Core\Form\FormStateInterface;
use Drupal\filter\FilterProcessResult;
use Drupal\filter\Plugin\FilterBase;

/**
 * Provides a filter for markdown.
 *
 * @Filter(
 *   id = "markdown",
 *   title = @Translation("Markdown"),
 *   description = @Translation("Allows content to be submitted using Markdown, a simple plain-text syntax that is filtered into valid HTML."),
 *   type = Drupal\filter\Plugin\FilterInterface::TYPE_MARKUP_LANGUAGE,
 * )
 */
class Markdown extends FilterBase implements MarkdownFilterInterface {

  /**
   * The Markdown parser.
   *
   * @var \Drupal\markdown\Plugin\Markdown\MarkdownParserInterface
   */
  protected $parser;

  /**
   * The MarkdownParser Plugin Manager service.
   *
   * @var \Drupal\markdown\MarkdownParserPluginManager
   */
  protected $parsers;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->parsers = \Drupal::service('plugin.manager.markdown.parser');
  }

  /**
   * {@inheritdoc}
   */
  public function getSetting($name, $default = NULL) {
    return isset($this->settings[$name]) ? $this->settings[$name] : $default;
  }

  /**
   * {@inheritdoc}
   */
  public function getParser() {
    if (!isset($this->parser)) {
      $this->parser = $this->parsers->createInstance($this->getSetting('parser'), ['filter' => $this]);
    }
    return $this->parser;
  }

  /**
   * {@inheritdoc}
   */
  public function isEnabled() {
    return !!$this->status;
  }

  /**
   * {@inheritdoc}
   *
   * @todo Refactor before release.
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $parser = $this->getParser();

    $parser_options = [];
    foreach ($this->parsers->getDefinitions() as $plugin_id => $definition) {
      $parser_options[$plugin_id] = $definition['label'];
    }

    if ($parser_options) {
      $form['parser'] = [
        '#type' => 'select',
        '#title' => $this->t('Markdown Parser'),
        '#options' => $parser_options,
        '#default_value' => $parser->getPluginId(),
      ];
    }
    else {
      $form['parser'] = [
        '#type' => 'item',
        '#title' => $this->t('No Markdown Parsers Found'),
        '#description' => $this->t('You need to use composer to install the <a href=":markdown_link">PHP Markdown Lib</a> and/or the <a href=":commonmark_link">CommonMark Lib</a>. Optionally you can use the Library module and place the PHP Markdown Lib in the root library directory, see more in README.', [
          ':markdown_link' => 'https://packagist.org/packages/michelf/php-markdown',
          ':commonmark_link' => 'https://packagist.org/packages/league/commonmark',
        ]),
      ];
    }

    $form['extensions'] = ['#type' => 'container'];
    foreach ($parser->getExtensions($this) as $extension) {
      $form['extensions'] += $extension->settingsForm($form['extensions'], $form_state, $this);
    }

//    $libraries_options = [];
//
//    if (class_exists('Michelf\MarkdownExtra')) {
//      $libraries_options['php-markdown'] = 'PHP Markdown';
//    }
//    elseif (\Drupal::moduleHandler()->moduleExists('libraries')) {
//      $library = libraries_detect('php-markdown');
//      if (!empty($library['installed'])) {
//        $libraries_options['php-markdown'] = 'PHP Markdown';
//      }
//    }
//
//    if (class_exists('League\CommonMark\CommonMarkConverter')) {
//      $libraries_options['commonmark'] = 'Commonmark';
//    }
//
//    if (isset($library['name'])) {
//      $form['markdown_status'] = [
//        '#title' => $this->t('Version'),
//        '#theme' => 'item_list',
//        '#items' => [
//          $library['name'] . ' ' . $library['version'],
//        ],
//      ];
//    }

    return $form;
  }

  /**
   * {@inheritdoc}
   *
   * @todo Refactor before release.
   */
  public function process($text, $langcode) {
    // Immediately return if text is empty.
    if (empty($text)) {
      return new FilterProcessResult($text);
    }

//    if (!empty($text)) {
//      switch ($this->settings['library']) {
//        case 'commonmark':
//          $converter = new CommonMarkConverter();
//          $text = $converter->convertToHtml($text);
//          break;
//        case 'php-markdown':
//          if (!class_exists('Michelf\MarkdownExtra') && \Drupal::moduleHandler()->moduleExists('libraries')) {
//            libraries_load('php-markdown', 'markdown-extra');
//          }
//          $text = MarkdownExtra::defaultTransform($text);
//          break;
//      }
//    }

    return new FilterProcessResult($this->getParser()->parse($text, \Drupal::languageManager()->getLanguage($langcode)));
  }

  /**
   * {@inheritdoc}
   *
   * @todo Refactor before release.
   */
  public function tips($long = FALSE) {
    $this->getParser()->tips($this, $long);
  }

}
