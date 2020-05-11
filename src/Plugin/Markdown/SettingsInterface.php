<?php

namespace Drupal\markdown\Plugin\Markdown;

/**
 * Interface for markdown plugin settings.
 */
interface SettingsInterface {

  /**
   * Provides the default settings for the plugin.
   *
   * @return array
   *   The default settings.
   */
  public static function defaultSettings();

  /**
   * Retrieves the default value for the setting.
   *
   * @param string $name
   *   The setting name. This can be a nested value using dot notation (e.g.
   *   "nested.property.key").
   *
   * @return mixed
   *   The settings value or NULL if not set.
   */
  public function getDefaultSetting($name);

  /**
   * Retrieves a setting for the plugin.
   *
   * @param string $name
   *   The setting name. This can be a nested value using dot notation (e.g.
   *   "nested.property.key").
   * @param mixed $default
   *   Optional. The default value to provide if $name isn't set.
   *
   * @return mixed
   *   The settings value or $default if not set.
   */
  public function getSetting($name, $default = NULL);

  /**
   * Retrieves the current settings.
   *
   * @return array
   *   The settings array
   */
  public function getSettings();

  /**
   * Flag indicating whether a setting exists.
   *
   * @param string $name
   *   The name of the setting to check.
   *
   * @return bool
   *   TRUE or FALSE
   */
  public function settingExists($name);

  /**
   * The array key name to use when the settings are nested in another array.
   *
   * @see \Drupal\markdown\Plugin\Markdown\CommonMark\CommonMark::getEnvironment()
   *
   * @return mixed
   *   The settings key.
   */
  public function settingsKey();

}
