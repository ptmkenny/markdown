<?php

namespace Drupal\markdown\Plugin\Markdown\Extension;

use Drupal\Core\Form\SubformStateInterface;
use Drupal\markdown\Plugin\Markdown\MarkdownPluginSettingsInterface;
use Drupal\markdown\Traits\MarkdownPluginSettingsTrait;
use League\CommonMark\EnvironmentAwareInterface;
use League\CommonMark\EnvironmentInterface;
use League\CommonMark\Extension\TableOfContents\TableOfContentsExtension as LeagueTableOfContentsExtension;

/**
 * @MarkdownExtension(
 *   id = "league/commonmark-ext-toc",
 *   installed = "\League\CommonMark\Extension\TableOfContents\TableOfContentsExtension",
 *   label = @Translation("Table Of Contents"),
 *   description = @Translation("Automatically inserts a table of contents into your document with links to the various headings."),
 *   url = "https://commonmark.thephpleague.com/extensions/table-of-contents/",
 *   requires = {
 *     "league/commonmark-ext-heading-permalink",
 *   }
 * )
 */
class TableOfContentsExtension extends CommonMarkExtensionBase implements EnvironmentAwareInterface, MarkdownPluginSettingsInterface {

  use MarkdownPluginSettingsTrait {
    buildSettingsForm as traitBuildSettingsForm;
    defaultSettings as traitDefaultSettings;
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'html_class' => 'table-of-contents',
      'max_heading_level' => 6,
      'min_heading_level' => 1,
      'normalize' => 'relative',
      'position' => 'top',
      'style' => 'bullet',
    ] + static::traitDefaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function extensionSettingsKey() {
    return 'table_of_contents';
  }

  /**
   * {@inheritdoc}
   */
  public function buildSettingsForm(array $element, SubformStateInterface $form_state) {
    $element = $this->traitBuildSettingsForm($element, $form_state);

    $element['html_class'] = [
      '#type' => 'textfield',
      '#title' => $this->t('HTML class'),
      '#default_value' => $form_state->getValue('html_class', $this->getSetting('html_class')),
      '#description' => $this->t("Sets the <code>&lt;ul&gt;</code> or <code>&lt;ol&gt;</code> tag's class attribute."),
    ];
    $headings = [
      '1' => 'H1', '2' => 'H2', '3' => 'H3',
      '4' => 'H4', '5' => 'H5', '6' => 'H6',
    ];
    $headings = array_combine(range(1, 6), array_map(function ($value) {
      return "h$value";
    }, range(1, 6)));
    $element['min_heading_level'] = [
      '#type' => 'select',
      '#title' => $this->t('Minimum heading level'),
      '#default_value' => $form_state->getValue('min_heading_level', $this->getSetting('min_heading_level')),
      '#description' => $this->t('Headings larger than this will be ignored, e.g. if set to <code>h2</code> then <code>h1</code> headings will be ignored.'),
      '#options' => $headings,
    ];
    $element['max_heading_level'] = [
      '#type' => 'select',
      '#title' => $this->t('Maximum heading level'),
      '#default_value' => $form_state->getValue('max_heading_level', $this->getSetting('max_heading_level')),
      '#description' => $this->t('Headings smaller than this will be ignored, e.g. if set to <code>h5</code> then <code>h6</code> headings will be ignored.'),
      '#options' => $headings,
    ];
    $element['normalize'] = [
      '#type' => 'select',
      '#title' => $this->t('Normalize'),
      '#default_value' => $form_state->getValue('normalize', $this->getSetting('normalize')),
      '#description' => $this->t('Strategy used when generating a (potentially-nested) list of headings.'),
      '#options' => [
        'as-is' => $this->t('As Is'),
        'flat' => $this->t('Flat'),
        'relative' => $this->t('Relative'),
      ],
    ];
    $element['position'] = [
      '#type' => 'select',
      '#title' => $this->t('Position'),
      '#default_value' => $form_state->getValue('position', $this->getSetting('position')),
      '#description' => $this->t('Where to place table of contents.'),
      '#options' => [
        'top' => $this->t('Top'),
        'before-headings' => $this->t('Before Headings'),
      ],
    ];
    $element['style'] = [
      '#type' => 'select',
      '#title' => $this->t('Style'),
      '#default_value' => $form_state->getValue('style', $this->getSetting('style')),
      '#description' => $this->t('HTML list style type to use when rendering the table of contents.'),
      '#options' => [
        'bullet' => $this->t('Unordered (Bulleted)'),
        'ordered' => $this->t('Ordered (Numbered)'),
      ],
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function setEnvironment(EnvironmentInterface $environment) {
    $environment->addExtension(new LeagueTableOfContentsExtension());
  }

}
