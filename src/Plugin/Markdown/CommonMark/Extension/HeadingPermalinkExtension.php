<?php

namespace Drupal\markdown\Plugin\Markdown\CommonMark\Extension;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\PluginFormInterface;
use Drupal\Core\Theme\ActiveTheme;
use Drupal\markdown\Annotation\MarkdownAllowedHtml;
use Drupal\markdown\Plugin\Markdown\AllowedHtmlInterface;
use Drupal\markdown\Plugin\Markdown\CommonMark\BaseExtension;
use Drupal\markdown\Plugin\Markdown\ExtensibleParserInterface;
use Drupal\markdown\Plugin\Markdown\ParserInterface;
use Drupal\markdown\Plugin\Markdown\SettingsInterface;
use Drupal\markdown\Traits\SettingsTrait;
use Drupal\markdown\Util\FilterHtml;
use League\CommonMark\ConfigurableEnvironmentInterface;
use League\CommonMark\Extension\HeadingPermalink\HeadingPermalinkExtension as LeagueHeadingPermalinkExtension;
use League\CommonMark\Extension\HeadingPermalink\HeadingPermalinkRenderer;

/**
 * @MarkdownExtension(
 *   id = "league/commonmark-ext-heading-permalink",
 *   label = @Translation("Heading Permalink"),
 *   description = @Translation("Makes all heading elements (&lt;h1&gt;, &lt;h2&gt;, etc) linkable so users can quickly grab a link to that specific part of the document."),
 *   installed = "\League\CommonMark\Extension\HeadingPermalink\HeadingPermalinkExtension",
 *   url = "https://commonmark.thephpleague.com/extensions/heading-permalinks/",
 * )
 * @MarkdownAllowedHtml(
 *   id = "league/commonmark-ext-heading-permalink",
 *   label = @Translation("Heading Permalink"),
 *   description = @Translation("Dynamically generated tags based on the contents of the 'Inner Contents' setting; updated each save."),
 *   installed = "\League\CommonMark\Extension\HeadingPermalink\HeadingPermalinkExtension",
 * )
 */
class HeadingPermalinkExtension extends BaseExtension implements AllowedHtmlInterface, PluginFormInterface, SettingsInterface {

  use SettingsTrait;

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
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function allowedHtmlTags(ParserInterface $parser, ActiveTheme $activeTheme = NULL) {
    $tags = [];
    if ($parser instanceof ExtensibleParserInterface && ($extension = $parser->extension($this->getPluginId())) && $extension instanceof SettingsInterface) {
      $tags = FilterHtml::tagsFromHtml($extension->getSetting('inner_contents'));
    }
    return $tags;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $element, FormStateInterface $form_state) {
    /** @var \Drupal\markdown\Form\SubformStateInterface $form_state */

    $element += $this->createSettingElement('html_class', [
      '#type' => 'textfield',
      '#title' => $this->t('HTML class'),
      '#description' => $this->t("The value of this nested configuration option should be a <code>string</code> that you want set as the <code>&lt;a&gt;</code> tag's class attribute."),
    ], $form_state);

    $element += $this->createSettingElement('id_prefix', [
      '#type' => 'textfield',
      '#title' => $this->t('ID Prefix'),
      '#description' => $this->t("This should be a <code>string</code> you want prepended to HTML IDs. This prevents generating HTML ID attributes which might conflict with others in your stylesheet. A dash separator (-) will be added between the prefix and the ID. You can instead set this to an empty string ('') if you don’t want a prefix."),
    ], $form_state);

    $element += $this->createSettingElement('inner_contents', [
      '#type' => 'textarea',
      '#description' => $this->t("This controls the HTML you want to appear inside of the generated <code>&lt;a&gt;</code> tag. Usually this would be something you'd style as some kind of link icon. By default, an embedded <a href=\":octicon-link\" target=\"_blank\">Octicon link SVG,</a> is provided, but you can replace this with any custom HTML you wish.<br>NOTE: The HTML tags and attributes saved here will be dynamically allowed using the corresponding Allowed HTML Plugin in \"Render Strategy\". This means that whatever is added here has the potential to open up security vulnerabilities.<br>If unsure or you wish for maximum security, use a non-HTML based placeholder (e.g. <code>{{ commonmark_heading_permalink_inner_contents }}</code>) value that you can replace post parsing in <code>hook_markdown_html_alter()</code>.", [
        ':octicon-link' => 'https://primer.style/octicons/link',
      ]),
    ], $form_state);

    $element += $this->createSettingElement('insert', [
      '#type' => 'select',
      '#description' => $this->t("This controls whether the anchor is added to the beginning of the <code>&lt;h1&gt;</code>, <code>&lt;h2&gt;</code> etc. tag or to the end."),
      '#options' => [
        'after' => $this->t('After'),
        'before' => $this->t('Before'),
      ],
    ], $form_state);

    $element += $this->createSettingElement('insert', [
      '#type' => 'textfield',
      '#description' => $this->t("This option sets the title attribute on the <code>&lt;a&gt;</code> tag."),
    ], $form_state);

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function register(ConfigurableEnvironmentInterface $environment) {
    $environment->addExtension(new LeagueHeadingPermalinkExtension());
  }

  /**
   * {@inheritdoc}
   */
  public function settingsKey() {
    return 'heading_permalink';
  }

}
