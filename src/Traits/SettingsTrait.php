<?php

namespace Drupal\markdown\Traits;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Form\FormStateInterface;
use Drupal\markdown\Util\SortArray;

/**
 * Trait for markdown plugins that implement settings.
 */
trait SettingsTrait {

  /**
   * Creates a setting element.
   *
   * @param string $name
   *   The setting name.
   * @param array $element
   *   The array element to construct. Note: this will be filled in with
   *   defaults if they're not provided.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current form state.
   * @param callable $valueTransformer
   *   Optional. Callback used to transform the setting value.
   *
   * @return array
   *   A render array with a child matching the name of the setting.
   *   This is primarily so that it can union with the parent element, e.g.
   *   `$form += $this->createSettingsElement(...)`.
   */
  protected function createSettingElement($name, array $element, FormStateInterface $form_state, callable $valueTransformer = NULL) {
    /** @var \Drupal\markdown\Form\SubformStateInterface $form_state */
    $settingName = $name;
    $parts = explode('.', $name);
    $name = array_pop($parts);

    // Prevent render if setting doesn't exist.
    if (!isset($element['#access']) && !$this->settingExists($settingName)) {
      $element['#access'] = FALSE;
    }

    // Create placeholder title so it can be replaced with a proper translation.
    if (!isset($element['#title'])) {
      $element['#title'] = "@TODO: $name";
    }

    // Handle initial setting value (Drupal names this #default_value).
    if (!isset($element['#default_value'])) {
      $value = $form_state->getValue($name, $this->getSetting($settingName));
      if ($valueTransformer) {
        $return = call_user_func($valueTransformer, $value);
        if (isset($return)) {
          $value = $return;
        }
      }
      $element['#default_value'] = $value;
    }

    // Handle real default setting value.
    $defaultValue = $this->getDefaultSetting($settingName);
    if (isset($defaultValue)) {
      if ($valueTransformer) {
        $return = call_user_func($valueTransformer, $defaultValue);
        if (isset($return)) {
          $defaultValue = $return;
        }
      }
      FormTrait::resetToDefault($element, $name, $defaultValue, $form_state);
    }

    return [$name => FormTrait::createElement($element)];
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    $pluginDefinition = $this->getPluginDefinition();
    $settings = isset($pluginDefinition['settings']) ? $pluginDefinition['settings'] : [];
    return [
      'settings' => NestedArray::mergeDeep($settings, static::defaultSettings($pluginDefinition)),
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings(array $pluginDefinition) {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getConfiguration() {
    $configuration = parent::getConfiguration();

    // Settings can change over time. Ensure only supported settings are saved.
    $settings = array_intersect_key($this->getSettings(), static::defaultSettings($this->getPluginDefinition()));

    // Sort settings (in case configuration was provided by form values).
    if ($settings) {
      SortArray::recursiveKeySort($settings);
    }

    // Only return settings that have changed from the default values.
    $configuration['settings'] = $settings;

    return $configuration;
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultSetting($name) {
    $defaultSettings = static::defaultSettings($this->getPluginDefinition());
    $parts = explode('.', $name);
    if (count($parts) == 1) {
      return isset($defaultSettings[$name]) ? $defaultSettings[$name] : NULL;
    }
    $value = NestedArray::getValue($defaultSettings, $parts, $key_exists);
    return $key_exists ? $value : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getSetting($name, $default = NULL) {
    $value = $this->config()->get("settings.$name");
    return isset($value) ? $value : $default;
  }

  /**
   * {@inheritdoc}
   */
  public function getSettings($runtime = FALSE) {
    return $this->config()->get('settings') ?: [];
  }

  /**
   * {@inheritdoc}
   */
  public function settingExists($name) {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsKey() {
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    // Intentionally do nothing. This is just required to be implemented.
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    // Intentionally do nothing. This is just required to be implemented.
  }

}
