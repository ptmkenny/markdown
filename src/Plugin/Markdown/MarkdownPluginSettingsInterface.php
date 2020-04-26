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
   *   The name of the setting to retrieve.
   *
   * @return mixed
   *   The settings value or NULL if not set.
   */
  public function getSetting($name);

  /**
   * Retrieves the current settings.
   *
   * @return array
   *   The settings array
   */
  public function getSettings();

  /**
   * Sets a specific setting.
   *
   * @param string $name
   *   The name of the setting to set.
   * @param mixed $value
   *   (optional) The value to set. If not provided it will be removed.
   */
  public function setSetting($name, $value = NULL);

  /**
   * Provides settings to an extension.
   *
   * @param array $settings
   *   The settings array.
   */
  public function setSettings(array $settings = []);

}
