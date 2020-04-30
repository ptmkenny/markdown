<?php

namespace Drupal\markdown\Traits;

trait MarkdownTrait {

  /**
   * The Markdown service.
   *
   * @var \Drupal\markdown\MarkdownInterface
   */
  protected static $markdown;

  /**
   * Retrieves the Markdown service.
   *
   * @return \Drupal\markdown\MarkdownInterface
   *   The Markdown service.
   */
  protected static function markdown() {
    if (!isset(static::$markdown)) {
      static::$markdown = \Drupal::service('markdown');
    }
    return static::$markdown;
  }

}
