<?php

namespace Drupal\markdown\Traits;

trait MarkdownParserPluginManagerTrait {

  /**
   * The Markdown Parser Plugin Manager service.
   *
   * @var \Drupal\markdown\MarkdownParserPluginManagerInterface
   */
  protected static $markdownParserPluginManager;

  /**
   * Retrieves the Markdown Parser Plugin Manager service.
   *
   * @return \Drupal\markdown\MarkdownParserPluginManagerInterface
   */
  protected static function markdownParserPluginManager() {
    if (!static::$markdownParserPluginManager) {
      static::$markdownParserPluginManager = \Drupal::service('plugin.manager.markdown.parser');
    }
    return static::$markdownParserPluginManager;
  }

}
