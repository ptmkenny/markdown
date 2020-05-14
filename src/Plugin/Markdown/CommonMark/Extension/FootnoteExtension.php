<?php

namespace Drupal\markdown\Plugin\Markdown\CommonMark\Extension;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\PluginFormInterface;
use Drupal\Core\Theme\ActiveTheme;
use Drupal\markdown\Plugin\Markdown\AllowedHtmlInterface;
use Drupal\markdown\Plugin\Markdown\CommonMark\BaseExtension;
use Drupal\markdown\Plugin\Markdown\ParserInterface;
use League\CommonMark\ConfigurableEnvironmentInterface;
use RZ\CommonMark\Ext\Footnote\FootnoteExtension as RZFootnoteExtension;

/**
 * Footnotes extension.
 *
 * @MarkdownExtension(
 *   id = "rezozero/commonmark-ext-footnotes",
 *   label = @Translation("Footnotes"),
 *   installed = "\RZ\CommonMark\Ext\Footnote\FootnoteExtension",
 *   description = @Translation("Adds the ability to create footnotes in markdown."),
 *   url = "https://github.com/rezozero/commonmark-ext-footnotes",
 * )
 * @MarkdownAllowedHtml(
 *   id = "rezozero/commonmark-ext-footnotes",
 *   label = @Translation("Footnotes"),
 *   installed = "\RZ\CommonMark\Ext\Footnote\FootnoteExtension",
 * )
 *
 * @todo Add settings if they ever become configurable.
 * @see https://github.com/rezozero/commonmark-ext-footnotes/issues/7
 */
class FootnoteExtension extends BaseExtension implements AllowedHtmlInterface, PluginFormInterface {

  /**
   * {@inheritdoc}
   */
  public function allowedHtmlTags(ParserInterface $parser, ActiveTheme $activeTheme = NULL) {
    return [
      'a' => [
        'href' => TRUE,
        'role' => TRUE,
        'rev' => TRUE,
      ],
      'div' => [
        'class' => TRUE,
        'id' => TRUE,
        'role' => TRUE,
      ],
      'hr' => [],
      'li' => [
        'class' => TRUE,
        'id' => TRUE,
        'role' => TRUE,
      ],
      'ol' => [],
      'sup' => [
        'id' => TRUE,
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $element, FormStateInterface $form_state) {
    /** @var \Drupal\markdown\Form\SubformStateInterface $form_state */

    // Add a note about core's aggressive XSS and how it affects footnotes.
    // @todo Remove note about core XSS bug/workaround.
    // @see https://www.drupal.org/project/markdown/issues/3136378
    $parent = &$form_state->getParentForm();
    $parent['#description'] = $this->t('NOTE: There is bug that prevents the footnote identifiers from being rendered properly due to aggressive XSS filtering. There is a <a href=":issue" target="_blank">temporary workaround</a>, but you must manually implement it in a custom module.', [
      ':issue' => 'https://www.drupal.org/project/markdown/issues/3131224#comment-13613381',
    ]);
    $parent['#description_display'] = 'before';

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function register(ConfigurableEnvironmentInterface $environment) {
    $environment->addExtension(new RZFootnoteExtension());
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    // Intentionally do nothing; implementation is required.
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    // Intentionally do nothing; implementation is required.
  }

}
