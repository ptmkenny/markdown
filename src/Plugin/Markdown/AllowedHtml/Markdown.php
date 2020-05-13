<?php

namespace Drupal\markdown\Plugin\Markdown\AllowedHtml;

use Drupal\Core\Plugin\PluginBase;
use Drupal\Core\Theme\ActiveTheme;
use Drupal\markdown\Plugin\Markdown\AllowedHtmlInterface;
use Drupal\markdown\Plugin\Markdown\ParserInterface;

/**
 * Global allowed HTML support.
 *
 * @MarkdownAllowedHtml(
 *   id = "markdown",
 *   description = @Translation("Provide common global attributes that are useful when dealing with Markdown generated output."),
 * )
 */
class Markdown extends PluginBase implements AllowedHtmlInterface {

  /**
   * {@inheritdoc}
   */
  public function allowedHtmlTags(ParserInterface $parser, ActiveTheme $activeTheme = NULL) {
    return [
      // @see https://developer.mozilla.org/en-US/docs/Web/HTML/Global_attributes
      '*' => [
        'aria*' => TRUE,
        'class' => TRUE,
        'id' => TRUE,
        'lang' => TRUE,
        'name' => TRUE,
        'tabindex' => TRUE,
        'title' => TRUE,
      ],
      'a' => [
        'href' => TRUE,
        'hreflang' => TRUE,
      ],
      'abbr' => [],
      'blockquote' => [
        'cite' => TRUE,
      ],
      'b' => [],
      'br' => [],
      'cite' => [],
      'code' => [],
      'div' => [],
      'em' => [],
      'h2' => [],
      'h3' => [],
      'h4' => [],
      'h5' => [],
      'h6' => [],
      'hr' => [],
      'i' => [],
      'img' => [
        'alt' => TRUE,
        'height' => TRUE,
        'src' => TRUE,
        'width' => TRUE,
      ],
      'li' => [],
      'ol' => [
        'start' => TRUE,
        'type' => [
          '1' => TRUE,
          'A' => TRUE,
          'I' => TRUE,
        ],
      ],
      'p' => [],
      'pre' => [],
      'span' => [],
      'strong' => [],
      'ul' => [
        'type' => TRUE,
      ],
    ];
  }

}
