<?php

namespace Drupal\markdown\Plugin\Markdown\Parsedown;

use Drupal\Core\Theme\ActiveTheme;
use Drupal\markdown\Plugin\Markdown\ParserInterface;

/**
 * Support for Parsedown Extra by Emanuil Rusev.
 *
 * @MarkdownParser(
 *   id = "erusev/parsedown-extra",
 *   label = @Translation("Parsedown Extra"),
 *   description = @Translation("Parser for Markdown with extra functionality."),
 *   url = "https://github.com/erusev/parsedown-extra",
 *   installed = "\ParsedownExtra",
 *   version = "\ParsedownExtra::version",
 *   weight = 20,
 * )
 * @MarkdownAllowedHtml(
 *   id = "erusev/parsedown-extra",
 *   label = @Translation("Parsedown Extra"),
 *   installed = "\ParsedownExtra",
 * )
 * @method \ParsedownExtra getParsedown()
 */
class ParsedownExtra extends Parsedown {

  /**
   * {@inheritdoc}
   */
  protected static $parsedownClass = '\\ParsedownExtra';

  /**
   * {@inheritdoc}
   */
  public function allowedHtmlTags(ParserInterface $parser, ActiveTheme $activeTheme = NULL) {
    return [
      'a' => [
        'rev' => TRUE,
      ],
      'abbr' => [],
      'dd' => [],
      'dl' => [],
      'dt' => [],
      'sup' => [],
    ];
  }

}
