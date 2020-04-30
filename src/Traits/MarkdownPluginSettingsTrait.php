<?php

namespace Drupal\markdown\Traits;

use Drupal\Core\Form\SubformStateInterface;

trait MarkdownPluginSettingsTrait {

  /**
   * {@inheritdoc}
   */
  public function buildSettingsForm(array $element, SubformStateInterface $form_state) {
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'settings' => isset($this->pluginDefinition['settings']) ? $this->pluginDefinition['settings'] : static::defaultSettings(),
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getConfiguration() {
    $configuration = parent::getConfiguration();
    $configuration['settings'] = $this->getSettings();
    return $configuration;
  }

  /**
   * {@inheritdoc}
   */
  public function getSetting($name, $default = NULL) {
    $value = $this->config->get("settings.$name");
    return isset($value) ? $value : $default;
  }

  /**
   * {@inheritdoc}
   */
  public function getSettings() {
    return $this->config->get('settings') ?: [];
  }

  /**
   * {@inheritdoc}
   */
  public function extensionSettingsKey() {
    return NULL;
  }

}
