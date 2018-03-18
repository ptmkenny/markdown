<?php

namespace Drupal\markdown\Plugin\Filter;

use Drupal\Core\Form\FormStateInterface;
use Drupal\filter\FilterProcessResult;
use Drupal\filter\Plugin\FilterBase;

/**
 * Provides a filter for Markdown.
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
   * @var \Drupal\markdown\MarkdownParsers
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
    $settings = $this->getSettings();
    return isset($settings[$name]) ? $settings[$name] : $default;
  }

  /**
   * {@inheritdoc}
   */
  public function getSettings() {
    return $this->settings;
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
  public function getParserSetting($name, $default = NULL) {
    $settings = $this->getParserSettings();
    return isset($settings[$name]) ? $settings[$name] : $default;
  }

  /**
   * {@inheritdoc}
   */
  public function getParserSettings() {
    return $this->getSetting('parser_settings', []);
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
    $parser_options = [];
    foreach ($this->parsers->getParsers() as $plugin_id => $plugin) {
      $parser_options[$plugin_id] = $plugin->label();
    }

    // Get the currently set parser.
    $parser = $this->getParser();

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

    // @todo Add parser specific settings.
    $form['parser_settings'] = ['#type' => 'container'];

    // Add any specific extension settings.
    $form['parser_settings']['extensions'] = ['#type' => 'container'];
    foreach ($parser->getExtensions($this) as $extension) {
      $form['extensions'] += $extension->settingsForm($form['extensions'], $form_state, $this);
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function process($text, $langcode) {
    // Only use the parser to process the text if it's not empty.
    if (!empty($text)) {
      $text = $this->getParser()->parse($text, \Drupal::languageManager()->getLanguage($langcode));
    }
    return new FilterProcessResult($text);
  }

  /**
   * {@inheritdoc}
   */
  public function tips($long = FALSE) {
    $this->getParser()->tips($this, $long);
  }

}
