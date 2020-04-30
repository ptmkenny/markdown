<?php

namespace Drupal\markdown\Traits;

trait MarkdownExtensionPluginManagerTrait {

  /**
   * The Markdown Extension Plugin Manager service.
   *
   * @var \Drupal\markdown\MarkdownExtensionPluginManagerInterface
   */
  protected static $markdownExtensionPluginManager;

  /**
   * Retrieves the Markdown Extension Plugin Manager service.
   *
   * @return \Drupal\markdown\MarkdownExtensionPluginManagerInterface
   */
  protected static function markdownExtensionPluginManager() {
    if (!static::$markdownExtensionPluginManager) {
      static::$markdownExtensionPluginManager = \Drupal::service('plugin.manager.markdown.extension');
    }
    return static::$markdownExtensionPluginManager;
  }

}
