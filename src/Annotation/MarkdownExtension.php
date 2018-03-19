<?php

namespace Drupal\markdown\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Class MarkdownExtension.
 *
 * @Annotation
 *
 * @Attributes({
 *   @Attribute("id", type = "string", required = true),
 *   @Attribute("parser", type = "string", required = true),
 * })
 */
class MarkdownExtension extends Plugin {

  /**
   * The parser identifier.
   *
   * @var string
   */
  protected $id;

  /**
   * The id of a MarkdownParser annotated plugin this extension belongs to.
   *
   * @var string
   */
  protected $parser;

  /**
   * The human-readable label.
   *
   * @var string|\Drupal\Core\Annotation\Translation
   */
  protected $label;

  /**
   * The description of the extension.
   *
   * @var string|\Drupal\Core\Annotation\Translation
   */
  protected $description;

}
