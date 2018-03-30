<?php

namespace Drupal\markdown\Plugin\Markdown;

use Drupal\Component\Utility\Xss;
use Drupal\Core\Language\LanguageInterface;

/**
 * Class PeclCmark.
 *
 * @MarkdownParser(
 *   id = "pecl/cmark",
 *   label = @Translation("PECL cmark/libcmark"),
 *   checkClass = "CommonMark\Parser",
 * )
 */
class PeclCmark extends BaseMarkdownParser {

  /**
   * {@inheritdoc}
   */
  public function getVersion() {
    // Retrieve the PECL extension version.
    $version = phpversion('cmark');

    // Extract the actual cmark library version being used.
    // @todo Revisit this to see if there's a better way.
    ob_start();
    phpinfo(INFO_MODULES);
    $php_info = ob_get_contents();
    ob_clean();
    preg_match('/libcmark library.*(\d+\.\d+\.\d+)/', $php_info, $matches);

    if (!empty($matches[1])) {
      $version .= '/' . $matches[1];
    }

    return $version;
  }

  /**
   * {@inheritdoc}
   */
  public function parse($markdown, LanguageInterface $language = NULL) {
    return trim(Xss::filterAdmin(\CommonMark\Render\HTML(\CommonMark\Parse($markdown))));
  }

}
