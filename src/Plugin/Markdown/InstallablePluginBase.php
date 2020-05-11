<?php

namespace Drupal\markdown\Plugin\Markdown;

/**
 * Base class for installable markdown plugins.
 */
abstract class InstallablePluginBase extends PluginBase implements InstallablePluginInterface {

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
