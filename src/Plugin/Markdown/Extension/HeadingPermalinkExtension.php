<?php

namespace Drupal\markdown\Plugin\Markdown\Extension;

use Drupal\Core\Form\SubformStateInterface;
use Drupal\markdown\Plugin\Markdown\MarkdownPluginSettingsInterface;
use Drupal\markdown\Traits\MarkdownPluginSettingsTrait;
use League\CommonMark\EnvironmentAwareInterface;
use League\CommonMark\EnvironmentInterface;
use League\CommonMark\Extension\HeadingPermalink\HeadingPermalinkExtension as LeagueHeadingPermalinkExtension;
use League\CommonMark\Extension\HeadingPermalink\HeadingPermalinkRenderer;

/**
 * @MarkdownExtension(
 *   id = "league/commonmark-ext-heading-permalink",
 *   installed = "\League\CommonMark\Extension\HeadingPermalink\HeadingPermalinkExtension",
 *   label = @Translation("Heading Permalink"),
 *   description = @Translation("Makes all heading elements (&lt;h1&gt;, &lt;h2&gt;, etc) linkable so users can quickly grab a link to that specific part of the document."),
 *   url = "https://commonmark.thephpleague.com/extensions/heading-permalinks/",
 * )
 */
class HeadingPermalinkExtension extends CommonMarkExtensionBase implements EnvironmentAwareInterface, MarkdownPluginSettingsInterface {

  use MarkdownPluginSettingsTrait {
    buildSettingsForm as traitBuildSettingsForm;
    defaultSettings as traitDefaultSettings;
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'html_class' => 'heading-permalink',
      'id_prefix' => 'user-content',
      'inner_contents' => HeadingPermalinkRenderer::DEFAULT_INNER_CONTENTS,
      'insert' => 'before',
      'title' => 'Permalink',
    ] + static::traitDefaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function extensionSettingsKey() {
    return 'heading_permalink';
  }

  /**
   * {@inheritdoc}
   */
  public function setEnvironment(EnvironmentInterface $environment) {
    $environment->addExtension(new LeagueHeadingPermalinkExtension());
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
      '#description' => $this->t("The value of this nested configuration option should be a <code>string</code> that you want set as the <code>&lt;a&gt;</code> tag's class attribute.")
    ];
    $element['id_prefix'] = [
      '#type' => 'textfield',
      '#title' => $this->t('ID Prefix'),
      '#default_value' => $form_state->getValue('id_prefix', $this->getSetting('id_prefix')),
      '#description' => $this->t("This should be a <code>string</code> you want prepended to HTML IDs. This prevents generating HTML ID attributes which might conflict with others in your stylesheet. A dash separator (-) will be added between the prefix and the ID. You can instead set this to an empty string ('') if you don’t want a prefix."),
    ];
    $element['inner_contents'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Inner Contents'),
      '#default_value' => $form_state->getValue('inner_contents', $this->getSetting('inner_contents')),
      '#description' => $this->t("This controls the HTML you want to appear inside of the generated <code>&lt;a&gt;</code> tag. Usually this would be something you'd style as some kind of link icon. By default, an embedded <a href=\":octicon-link\" target=\"_blank\">Octicon link SVG,</a> is provided, but you can replace this with any custom HTML you wish.", [
        ':octicon-link' => 'https://primer.style/octicons/link'
      ]),
    ];
    $element['insert'] = [
      '#type' => 'select',
      '#title' => $this->t('Insert'),
      '#default_value' => $form_state->getValue('insert', $this->getSetting('insert')),
      '#description' => $this->t("This controls whether the anchor is added to the beginning of the <code>&lt;h1&gt;</code>, <code>&lt;h2&gt;</code> etc. tag or to the end."),
      '#options' => [
        'after' => $this->t('After'),
        'before' => $this->t('Before'),
      ],
    ];
    $element['title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Title'),
      '#default_value' => $form_state->getValue('title', $this->getSetting('title')),
      '#description' => $this->t("This option sets the title attribute on the <code>&lt;a&gt;</code> tag."),
    ];
    return $element;
  }

}
