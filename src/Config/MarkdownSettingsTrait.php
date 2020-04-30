<?php

namespace Drupal\markdown\Config;

use Drupal\Component\Utility\NestedArray;
use Drupal\markdown\Traits\MarkdownParserPluginManagerTrait;

/**
 * {@internal}
 */
trait MarkdownSettingsTrait {

  use MarkdownParserPluginManagerTrait;

  /**
   * {@inheritdoc}
   */
  public function getParser(array $configuration = []) {
    return static::markdownParserPluginManager()->createInstance($this->getParserId(), $this->getParserConfiguration($configuration));
  }

  /**
   * {@inheritdoc}
   */
  public function getParserId($fallback = TRUE) {
    $parserId = $this->get($this->prefixKey('id'));
    if (!$parserId && $fallback) {
      $parserId = static::markdownParserPluginManager()->firstInstalledPluginId();
    }
    return $parserId;
  }

  /**
   * {@inheritdoc}
   */
  public function getParserConfiguration(array $configuration = []) {
    return NestedArray::mergeDeep([
      'extensions' => $this->getParserExtensions(),
      'settings' => $this->getParserSettings(),
    ], $configuration);
  }

  /**
   * {@inheritdoc}
   */
  public function getParserExtensionSetting($extension, $name, $default = NULL) {
    $value = $this->get($this->prefixKey("extensions.$extension.$name"));
    return isset($value) ? $value : $default;
  }

  /**
   * {@inheritdoc}
   */
  public function getParserExtensionSettings($extension) {
    return $this->get($this->prefixKey("extensions.$extension")) ?: [];
  }

  /**
   * {@inheritdoc}
   */
  public function getParserExtensions($keyed = FALSE) {
    $extensions = $this->get($this->prefixKey('extensions')) ?: [];

    if (!$keyed) {
      return $extensions;
    }

    $data = [];
    foreach ($extensions as $extension) {
      if (isset($extension['id'])) {
        $extensionId = $extension['id'];
        unset($extension['id']);
        $data[$extensionId] = $extension;
      }
    }

    return $data;
  }

  /**
   * {@inheritdoc}
   */
  public function getParserSetting($name, $default = NULL) {
    $value = $this->get($this->prefixKey("settings.$name"));
    return isset($value) ? $value : $default;
  }

  /**
   * {@inheritdoc}
   */
  public function getParserSettings() {
    return $this->get($this->prefixKey('settings')) ?: [];
  }

}
