<?php

namespace Drupal\markdown\Plugin\Markdown\Extension;

use Drupal\markdown\Annotation\MarkdownExtension;
use Drupal\markdown\Plugin\Markdown\MarkdownInstallablePluginBase;
use Drupal\markdown\Plugin\Markdown\MarkdownPluginSettingsInterface;
use Drupal\markdown\Traits\MarkdownStatesTrait;

/**
 * Base class for markdown extensions.
 *
 * @MarkdownExtension(
 *   id = "_broken",
 *   label = @Translation("Missing Extension"),
 * )
 */
class MarkdownExtensionBase extends MarkdownInstallablePluginBase implements MarkdownExtensionInterface {

  use MarkdownStatesTrait;

  /**
   * {@inheritdoc}
   */
  public function getConfiguration() {
    $configuration = parent::getConfiguration();
    $configuration['enabled'] = $this->isEnabled();

    // Only provide settings if extension is enabled.
    if ($this instanceof MarkdownPluginSettingsInterface && !$this->isEnabled()) {
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
