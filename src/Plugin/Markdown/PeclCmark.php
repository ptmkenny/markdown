<?php

namespace Drupal\markdown\Plugin\Markdown;

use Drupal\Core\Language\LanguageInterface;
use Drupal\markdown\Traits\MarkdownParserBenchmarkTrait;

/**
 * @MarkdownParser(
 *   id = "pecl/cmark",
 *   label = @Translation("PECL cmark/libcmark"),
 *   url = "https://pecl.php.net/package/cmark",
 * )
 */
class PeclCmark extends BaseParser implements MarkdownParserBenchmarkInterface {

  use MarkdownParserBenchmarkTrait;

  /**
   * {@inheritdoc}
   */
  public static function installed(): bool {
    return class_exists('\\CommonMark\\Parser');
  }

  /**
   * {@inheritdoc}
   */
  public static function version(): string {
    if (static::installed()) {
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
  }

  /**
   * {@inheritdoc}
   */
  public function convertToHtml($markdown, LanguageInterface $language = NULL) {
    try {
      if (is_string($markdown)) {
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
