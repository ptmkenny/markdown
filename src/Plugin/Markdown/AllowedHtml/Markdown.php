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
    // @see https://developer.mozilla.org/en-US/docs/Web/HTML/Global_attributes
    return [
      '*' => [
        'aria*' => TRUE,
        'class' => TRUE,
        'id' => TRUE,
        'lang' => TRUE,
        'name' => TRUE,
        'tabindex' => TRUE,
        'title' => TRUE,
      ],
    ];
  }

}
