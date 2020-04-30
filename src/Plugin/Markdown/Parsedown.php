<?php

namespace Drupal\markdown\Plugin\Markdown;

use Drupal\Core\Form\SubformStateInterface;
use Drupal\Core\Language\LanguageInterface;

/**
 * @MarkdownParser(
 *   id = "erusev/parsedown",
 *   label = @Translation("Parsedown"),
 *   url = "https://parsedown.org",
 *   weight = 21,
 * )
 */
class Parsedown extends MarkdownParserBase implements MarkdownPluginSettingsInterface {

  /**
   * The Parsedown class to use.
   *
   * @var string
   */
  protected static $parsedownClass = '\\Parsedown';

  /**
   * The Parsedown instance.
   *
   * @var \Parsedown
   */
  protected $parsedown;

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'breaks_enabled' => FALSE,
      'markup_escaped' => FALSE,
      'safe_mode' => FALSE,
      'urls_linked' => TRUE,
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function buildSettingsForm(array $element, SubformStateInterface $form_state) {
    $element = parent::buildSettingsForm($element, $form_state);

    $element['todo']['#markup'] = 'TODO: Implement \Drupal\markdown\Plugin\Markdown\Parsedown::buildSettingsForm() method.';
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public static function installed() {
    return class_exists(static::$parsedownClass);
  }

  /**
   * {@inheritdoc}
   */
  public static function version() {
    if (static::installed()) {
      $class = static::$parsedownClass;
      return $class::version;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function convertToHtml($markdown, LanguageInterface $language = NULL) {
    return $this->parsedown()->text($markdown);
  }

  /**
   * Retrieves the Parsedown instance.
   *
   * @return \Parsedown
   *   A PHP Markdown parser.
   */
  protected function parsedown() {
    if (!$this->parsedown) {
      $this->parsedown = new static::$parsedownClass();
      foreach ($this->getSettings() as $name => $value) {
        if ($method = $this->getSettingMethod($name)) {
          $this->parsedown->$method($value);
        }
      }
    }
    return $this->parsedown;
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
