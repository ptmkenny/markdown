<?php

namespace Drupal\markdown\Plugin\Markdown;

/**
 * Base class for markdown extensions.
 */
abstract class BaseExtension extends InstallablePluginBase implements ExtensionInterface {

  /**
   * {@inheritdoc}
   */
  public function getConfiguration() {
    $configuration = parent::getConfiguration();
    $configuration['enabled'] = $this->isEnabled();

    // Only provide settings if extension is enabled.
    if ($this instanceof SettingsInterface && !$this->isEnabled()) {
      $configuration['settings'] = [];
    }

    return $configuration;
  }

  /**
   * {@inheritdoc}
   */
  protected function getConfigType() {
    return "markdown_extension_settings.{$this->getPluginId()}";
  }

  /**
   * {@inheritdoc}
   */
  public function isEnabled() {
    return $this->isInstalled() && !empty($this->configuration['enabled']);
  }

  /**
   * {@inheritdoc}
   */
  public function requires() {
    return isset($this->pluginDefinition['requires']) ? $this->pluginDefinition['requires'] : [];
  }

  /**
   * {@inheritdoc}
   */
  public function requiredBy() {
    return isset($this->pluginDefinition['requiredBy']) ? $this->pluginDefinition['requiredBy'] : [];
  }

}
