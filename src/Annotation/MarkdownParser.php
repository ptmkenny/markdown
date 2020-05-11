<?php

namespace Drupal\markdown\Annotation;

/**
 * Markdown Parser Annotation.
 *
 * @Annotation
 *
 * @Attributes({
 *   @Attribute("id", required = true, type = "string"),
 *   @Attribute("bundledExtensions", type = "string[]"),
 * })
 */
class MarkdownParser extends BaseMarkdownAnnotation {

  /**
   * List of markdown extension plugin identifiers, bundled with the parser.
   *
   * @var string[]
   */
  protected $bundledExtensions = [];

}
