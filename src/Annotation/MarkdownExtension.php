<?php

namespace Drupal\markdown\Annotation;

/**
 * @Annotation
 *
 * @Attributes({
 *   @Attribute("id", type = "string", required = true),
 *   @Attribute("requires", type = "string[]"),
 * })
 */
class MarkdownExtension extends BaseMarkdownAnnotation {

  /**
   * An array of extension plugin identifiers that is required.
   *
   * @var string[]
   */
  protected $requires = [];

}
