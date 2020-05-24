<?php

namespace Drupal\markdown\Plugin\Markdown;

/**
 * The extension used as a fallback when the requested one doesn't exist.
 *
 * @MarkdownExtension(
 *   id = "_missing_extension",
 *   label = @Translation("Missing Extension"),
 *   installed = false,
 * )
 */
class MissingExtension extends InstallablePluginBase implements ExtensionInterface {

  /**
   * {@inheritdoc}
   */
  protected function getConfigType() {
    return 'markdown_extension';
  }

  /**
   * {@inheritdoc}
   */
  public function getConfiguration() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function isEnabled() {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function requires() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function requiredBy() {
    return [];
  }

}
