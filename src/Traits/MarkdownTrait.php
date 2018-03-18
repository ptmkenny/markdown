<?php

namespace Drupal\markdown\Traits;

/**
 * Trait MarkdownTrait.
 */
trait MarkdownTrait {

  /**
   * The Markdown service.
   *
   * @var \Drupal\markdown\Markdown
   */
  protected static $markdown;

  /**
   * Retrieves the Markdown service.
   *
   * @return \Drupal\markdown\Markdown
   *   A MarkdownParser plugin.
   */
  public function markdown() {
    if (!isset(static::$markdown)) {
      static::$markdown = \Drupal::service('markdown');
    }
    return static::$markdown;
  }

}
