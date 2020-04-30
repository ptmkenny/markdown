<?php

namespace Drupal\markdown\Plugin\Markdown;

abstract class MarkdownInstallablePluginBase extends MarkdownPluginBase implements MarkdownInstallablePluginInterface {

  /**
   * {@inheritdoc}
   */
  public static function installed() {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public static function version() {
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getLabel($version = TRUE) {
    $label = parent::getLabel();
    if ($version && ($version = $this->getVersion())) {
      $label .= " ($version)";
    }
    return $label;
  }

  /**
   * {@inheritdoc}
   */
  public function getVersion() {
    return isset($this->pluginDefinition['version']) ? $this->pluginDefinition['version'] : NULL;
  }


  /**
   * {@inheritdoc}
   */
  public function isInstalled() {
    return isset($this->pluginDefinition['installed']) ? !!$this->pluginDefinition['installed'] : FALSE;
  }

}
