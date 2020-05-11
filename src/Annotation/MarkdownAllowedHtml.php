<?php

namespace Drupal\markdown\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Markdown Allowed HTML Annotation.
 *
 * @Annotation
 */
class MarkdownAllowedHtml extends Plugin {

  /**
   * The parser identifier.
   *
   * @var string
   */
  protected $id;

  /**
   * The plugin description.
   *
   * @var string
   */
  protected $description;

  /**
   * The plugin label.
   *
   * @var string
   */
  protected $label;

  /**
   * Flag indicating whether plugin requires a filter association.
   *
   * @var string
   */
  protected $requiresFilter;

}
