<?php

namespace Drupal\markdown\Traits;

/**
 * Trait for utilizing the Renderer service.
 */
trait RendererTrait {

  /**
   * The Renderer service.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected static $renderer;

  /**
   * Retrieves the Renderer service.
   *
   * @return \Drupal\Core\Render\RendererInterface
   *   The Renderer service.
   */
  protected function renderer() {
    if (!static::$renderer) {
      static::$renderer = \Drupal::service('renderer');
    }
    return static::$renderer;
  }

}
