<?php

namespace Drupal\markdown\Plugin\Filter;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\filter\FilterProcessResult;
use Drupal\filter\Plugin\FilterBase;
use Drupal\markdown\Plugin\Markdown\ExtensibleMarkdownParserInterface;
use Drupal\markdown\Traits\MarkdownStatesTrait;

/**
 * Provides a filter for Markdown.
 *
 * @Filter(
 *   id = "markdown",
 *   title = @Translation("Markdown"),
 *   description = @Translation("Allows content to be submitted using Markdown, a simple plain-text syntax that is filtered into valid HTML."),
 *   type = Drupal\filter\Plugin\FilterInterface::TYPE_MARKUP_LANGUAGE,
 *   weight = -15,
 *   settings = {
 *     "parser" = "thephpleague/commonmark",
 *     "parser_settings" = {},
 *   },
 * )
 */
class Markdown extends FilterBase implements MarkdownFilterInterface {

  use MarkdownStatesTrait;

  /**
   * The Markdown parser as set by the filter.
   *
   * @var \Drupal\markdown\Plugin\Markdown\MarkdownParserInterface
   */
  protected $parser;

  /**
   * The Markdown Parser Manager service.
   *
   * @var \Drupal\markdown\MarkdownParserManager
   */
  protected $parserManager;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->parserManager = \Drupal::service('plugin.manager.markdown.parser');
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
      $this->parser = $this->parserManager->createInstance($this->getSetting('parser', 'thephpleague/commonmark'), ['filter' => $this]);
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
    $parents = $form['#parents'];
    $defaultParser = $form_state->getValue(array_merge($parents, ['parser']), $this->getParser()->getPluginId());
    if ($labels = $this->parserManager->getLabels()) {
      $id = Html::getUniqueId('markdown-parser-ajax');

      // Build a wrapper for the ajax response.
      $form['ajax'] = [
        '#type' => 'container',
        '#attributes' => ['id' => $id],
        '#parents' => $parents,
      ];

      $form['ajax']['parser'] = [
        '#type' => 'select',
        '#title' => $this->t('Parser'),
        '#options' => $labels,
        '#default_value' => $defaultParser,
        '#ajax' => [
          'callback' => [$this, 'ajaxChangeParser'],
          'event' => 'change',
          'wrapper' => $id,
        ],
      ];
    }
    else {
      $form['ajax']['parser'] = [
        '#type' => 'item',
        '#title' => $this->t('No Markdown Parsers Found'),
        '#description' => $this->t('Visit the <a href=":system.status">@system.status</a> page for more details.', [
          '@system.status' => $this->t('Status report'),
          ':system.status' => \Drupal::urlGenerator()->generate('system.status'),
        ]),
      ];
    }

    if ($defaultParser && ($parser = $this->parserManager->createInstance($defaultParser, ['filter' => $this])) && $parser instanceof ExtensibleMarkdownParserInterface && ($extensions = $parser->getExtensions())) {
      // @todo Add parser specific settings.
      $form['ajax']['parser_settings'] = [
        '#type' => 'fieldset',
        '#title' => $this->t('Extensions'),
      ];

      // Add any specific extension settings.
      $form['ajax']['parser_settings']['extensions'] = ['#type' => 'container'];
      foreach ($extensions as $pluginId => $extension) {
        // Extension Details.
        $form['ajax']['parser_settings']['extensions'][$pluginId] = [
          '#type' => 'details',
          '#title' => ($url = $extension->getUrl()) ? Link::fromTextAndUrl($extension->getLabel(), $url) : $extension->getLabel(),
          '#description' => $extension->getDescription(),
          '#open' => $extension->isEnabled(),
          '#array_parents' => array_merge($parents, ['parser_settings', 'extensions']),
        ];

        // Extension enabled checkbox.
        $form['ajax']['parser_settings']['extensions'][$pluginId]['enabled'] = [
          '#type' => 'checkbox',
          '#title' => $this->t('Enabled'),
          '#default_value' => $extension->isEnabled(),
        ];

        // Extension settings.
        $selector = $this->getSatesSelector(array_merge($parents, ['parser_settings', 'extensions', $pluginId]), 'enabled');
        $form['ajax']['parser_settings']['extensions'][$pluginId]['settings'] = [
          '#type' => 'container',
          '#states' => [
            'visible' => [
              $selector => ['checked' => TRUE],
            ],
          ],
        ];
        $form['ajax']['parser_settings']['extensions'][$pluginId]['settings'] = $extension->settingsForm($form['ajax']['parser_settings']['extensions'][$pluginId]['settings'], $form_state, $this);
      }
    }

    return $form;
  }

  /**
   * The AJAX callback used to return the parser ajax wrapper.
   */
  public function ajaxChangeParser(array $form, FormStateInterface $form_state) {
    $parents = $form_state->getTriggeringElement()['#array_parents'];
    array_pop($parents);
    return NestedArray::getValue($form, $parents);
  }

  public static function processTextFormat(&$element, FormStateInterface $form_state, &$complete_form) {
    $formats = filter_formats();
    /** @var \Drupal\filter\FilterFormatInterface $format */
    $format = isset($formats[$element['#format']]) ? $formats[$element['#format']] : FALSE;
    if ($format && ($markdown = $format->filters('markdown')) && $markdown instanceof MarkdownFilterInterface && $markdown->isEnabled()) {
      $element['format']['help']['about'] = [
        '#type' => 'link',
        '#title' => t('@iconStyling with Markdown is supported', [
          // Shamelessly copied from GitHub's Octicon icon set.
          // @todo Revisit this?
          // @see https://github.com/primer/octicons/blob/master/lib/svg/markdown.svg
          '@icon' => new FormattableMarkup('<svg class="octicon octicon-markdown v-align-bottom" viewBox="0 0 16 16" version="1.1" width="16" height="16" aria-hidden="true" style="fill: currentColor;margin-right: 5px;vertical-align: text-bottom;"><path fill-rule="evenodd" d="M14.85 3H1.15C.52 3 0 3.52 0 4.15v7.69C0 12.48.52 13 1.15 13h13.69c.64 0 1.15-.52 1.15-1.15v-7.7C16 3.52 15.48 3 14.85 3zM9 11H7V8L5.5 9.92 4 8v3H2V5h2l1.5 2L7 5h2v6zm2.99.5L9.5 8H11V5h2v3h1.5l-2.51 3.5z"></path></svg>', []),
        ]),
        '#url' => Url::fromRoute('filter.tips_all')->setOptions([
          'attributes' => [
            'class' => ['markdown'],
            'target' => '_blank',
        ]]),
      ];
    }
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function process($text, $langcode) {
    // Only use the parser to process the text if it's not empty.
    if (!empty($text)) {
      $language = \Drupal::languageManager()->getLanguage($langcode);
      $markdown = $this->getParser()->parse($text, $language);

      // Enable all tags, let other filters (i.e. filter_html) handle that.
      $text = $markdown->setAllowedTags(TRUE)->getHtml();
    }
    return new FilterProcessResult($text);
  }

  /**
   * {@inheritdoc}
   */
  public function tips($long = FALSE) {
    return $this->getParser()->tips($long);
  }

}
