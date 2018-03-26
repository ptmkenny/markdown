<?php

namespace Drupal\markdown\Plugin\Markdown;

use Drupal\Component\Utility\Xss;
use Drupal\Core\Language\LanguageInterface;

/**
 * Class Parsedown.
 *
 * @MarkdownParser(
 *   id = "parsedown",
 *   label = @Translation("Parsedown/ParsedownExtra"),
 *   checkClass = "ParsedownExtra",
 * )
 */
class Parsedown extends BaseMarkdownParser {

  /**
   * MarkdownExtra parsers, keyed by filter identifier.
   *
   * @var \ParsedownExtra[]
   */
  protected static $parsers = [];

  protected static $settingsMethodMap = [
    'breaks_enabled' => 'setBreaksEnabled',
    'markup_escaped' => 'setMarkupEscaped',
    'safe_mode' => 'setSafeMode',
    'urls_linked' => 'setUrlsLinked',
  ];

  /**
   * Retrieves the PHP Markdown parser.
   *
   * @return \ParsedownExtra
   *   A PHP Markdown parser.
   */
  public function getParser() {
    if (!isset(static::$parsers[$this->filterId])) {
      $parser = new \ParsedownExtra();
      if ($this->filter) {
        foreach ($this->settings as $name => $value) {
          if ($method = $this->getSettingMethod($name)) {
            $parser->$method($value);
          }
        }
      }
      static::$parsers[$this->filterId] = $parser;
    }
    return static::$parsers[$this->filterId];
  }

  /**
   * Retrieves the method used to configure a specific setting.
   *
   * @param string $name
   *   The name of the setting.
   *
   * @return string|null
   *   The method name or NULL if method does not exist.
   */
  protected function getSettingMethod($name) {
    return isset(static::$settingsMethodMap[$name]) ? static::$settingsMethodMap[$name] : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getVersion() {
    return \Parsedown::version . '/' . \ParsedownExtra::version;
  }

  /**
   * {@inheritdoc}
   */
  public function parse($markdown, LanguageInterface $language = NULL) {
    return trim(Xss::filterAdmin($this->getParser()->text($markdown)));
  }

}
