<?php

namespace Drupal\markdown\Plugin\Markdown\Pecl;

use Drupal\Core\Language\LanguageInterface;
use Drupal\markdown\Plugin\Markdown\AllowedHtmlInterface;
use Drupal\markdown\Plugin\Markdown\BaseParser;
use Drupal\markdown\Traits\ParserAllowedHtmlTrait;

/**
 * @MarkdownAllowedHtml(
 *   id = "commonmark-pecl",
 * )
 * @MarkdownParser(
 *   id = "commonmark-pecl",
 *   label = @Translation("CommonMark PECL"),
 *   description = @Translation("CommonMark PECL extension using libcmark."),
 *   weight = 10,
 *   libraries = {
 *     @PhpExtension(
 *       id = "ext-cmark",
 *       object = "\CommonMark\Parser",
 *       url = "https://pecl.php.net/package/cmark",
 *     ),
 *   }
 * )
 */
class Cmark extends BaseParser implements AllowedHtmlInterface {

  use ParserAllowedHtmlTrait;

  /**
   * {@inheritdoc}
   */
  protected function convertToHtml($markdown, LanguageInterface $language = NULL) {
    try {
      if (is_string($markdown)) {
        // NOTE: these are functions, not classes.
        $node = \CommonMark\Parse($markdown);
        return \CommonMark\Render\HTML($node);
      }
    }
    catch (\Exception $e) {
      // Intentionally left blank.
    }
    return '';
  }

}
