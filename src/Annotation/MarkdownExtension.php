<?php

namespace Drupal\markdown\Annotation;

/**
 * Class MarkdownExtension.
 *
 * @Annotation
 *
 * @Attributes({
 *   @Attribute("id", type = "string", required = true),
 *   @Attribute("parsers", type = "string[]"),
 * })
 */
class MarkdownExtension extends BaseMarkdownAnnotation {

  /**
   * An identifier or array of parser identifiers this extension belongs to.
   *
   * @var string|string[]
   */
  protected $parsers;

}
