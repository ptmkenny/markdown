<?php

namespace Drupal\markdown\Plugin\Markdown;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Language\LanguageInterface;

/**
 * @MarkdownParser(
 *   id = "erusev/parsedown",
 *   label = @Translation("Parsedown"),
 *   url = "https://parsedown.org",
 *   weight = 21,
 * )
 */
class Parsedown extends BaseParser {

  /**
   * The parser class.
   *
   * @var string
   */
  protected static $parserClass = '\\Parsedown';

  /**
   * MarkdownExtra parsers, keyed by filter identifier.
   *
   * @var \Parsedown[]
   */
  protected static $parsers = [];

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return NestedArray::mergeDeep(
      parent::defaultSettings(),
      [
        'breaks_enabled' => FALSE,
        'markup_escaped' => FALSE,
        'safe_mode' => FALSE,
        'urls_linked' => TRUE,
      ]
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function installed() {
    return class_exists(static::$parserClass);
  }

  /**
   * {@inheritdoc}
   */
  public static function version() {
    if (static::installed()) {
      $class = static::$parserClass;
      return $class::version;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function convertToHtml($markdown, LanguageInterface $language = NULL) {
    return $this->getParser()->text($markdown);
  }

  /**
   * Retrieves the PHP Markdown parser.
   *
   * @return \Parsedown
   *   A PHP Markdown parser.
   */
  public function getParser() {
    if (!isset(static::$parsers[$this->filterId])) {
      $parser = new static::$parserClass();
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
    $map = static::settingMethodMap();
    return isset($map[$name]) ? $map[$name] : NULL;
  }

  /**
   * A map of setting <-> method.
   *
   * @return array
   */
  protected static function settingMethodMap() {
    return [
      'breaks_enabled' => 'setBreaksEnabled',
      'markup_escaped' => 'setMarkupEscaped',
      'safe_mode' => 'setSafeMode',
      'strict_mode' => 'setStrictMode',
      'urls_linked' => 'setUrlsLinked',
    ];
  }


}
