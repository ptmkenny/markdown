<?php

namespace Drupal\markdown\Plugin\Markdown;

use Drupal\Core\Form\SubformStateInterface;

interface MarkdownPluginSettingsInterface {

  /**
   * Builds the form element specific to this plugin's settings.
   *
   * @param array $element
   *   The element render array for the extension configuration form.
   * @param \Drupal\Core\Form\SubformStateInterface $form_state
   *   The current sub-form state of the form.
   *
   * @return array
   *   A render array representing the plugin's settings.
   */
  public function buildSettingsForm(array $element, SubformStateInterface $form_state);

  /**
   * Provides the default settings for the plugin.
   *
   * @return array
   *   The default settings.
   */
  public static function defaultSettings();

  /**
   * Retrieves a setting for the plugin.
   *
   * @param string $name
   *   The setting name.
   * @param mixed $default
   *   Optional. The default value to provide if $name isn't set.
   *
   * @return mixed
   *   The settings value or NULL if not set.
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
   * The key used to specify the extension's settings inside parser settings.
   *
   * Note: this requires that the parser supports this for it to be useful.
   * Because each parser and extension architecture varies, how it is exactly
   * used may vary.
   *
   * @see \Drupal\markdown\Plugin\Markdown\LeagueCommonMark::getEnvironment()
   *
   * @return mixed
   */
  public function extensionSettingsKey();

}
