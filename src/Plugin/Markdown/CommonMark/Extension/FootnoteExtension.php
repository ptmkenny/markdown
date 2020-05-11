<?php

namespace Drupal\markdown\Plugin\Markdown\CommonMark\Extension;

use Drupal\Core\Theme\ActiveTheme;
use Drupal\markdown\Plugin\Markdown\AllowedHtmlInterface;
use Drupal\markdown\Plugin\Markdown\CommonMark\BaseExtension;
use Drupal\markdown\Plugin\Markdown\ParserInterface;
use League\CommonMark\EnvironmentAwareInterface;
use League\CommonMark\EnvironmentInterface;
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
class FootnoteExtension extends BaseExtension implements AllowedHtmlInterface, EnvironmentAwareInterface {

  /**
   * {@inheritdoc}
   */
  public function allowedHtmlTags(ParserInterface $parser, ActiveTheme $activeTheme = NULL) {
    return [
      'a' => [
        'rev' => TRUE,
        'role' => TRUE,
      ],
      'sup' => [],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function setEnvironment(EnvironmentInterface $environment) {
    $environment->addExtension(new RZFootnoteExtension());
  }

}
