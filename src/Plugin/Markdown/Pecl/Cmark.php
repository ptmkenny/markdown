<?php

namespace Drupal\markdown\Plugin\Markdown\Pecl;

use CommonMark\Parse;
use CommonMark\Render\HTML;
use Drupal\Core\Language\LanguageInterface;
use Drupal\markdown\Plugin\Markdown\BaseParser;

/**
 * @MarkdownParser(
 *   id = "pecl/cmark",
 *   label = @Translation("PECL cmark/libcmark"),
 *   description = @Translation("PECL CommonMark extension using libcmark."),
 *   url = "https://pecl.php.net/package/cmark",
 *   installed = "\CommonMark\Parser",
 *   version = "\Drupal\markdown\Plugin\Markdown\Pecl\Cmark::version",
 *   weight = 10,
 * )
 */
class Cmark extends BaseParser {

  /**
   * {@inheritdoc}
   */
  public static function version() {
    // Retrieve the PECL extension version.
    $version = [phpversion('cmark')];

    // Extract the actual cmark library version being used.
    // @todo Revisit this to see if there's a better way.
    ob_start();
    phpinfo(INFO_MODULES);
    $php_info = ob_get_contents();
    ob_clean();
    preg_match('/libcmark library.*(\d+\.\d+\.\d+)/', $php_info, $matches);
    if (!empty($matches[1])) {
      $version[] = $matches[1];
    }

    return implode('/', $version);
  }

  /**
   * {@inheritdoc}
   */
  protected function convertToHtml($markdown, LanguageInterface $language = NULL) {
    try {
      if (is_string($markdown)) {
        $node = Parse($markdown);
        return HTML($node);
      }
    }
    catch (\Exception $e) {
      // Intentionally left blank.
    }
    return '';
  }

}
