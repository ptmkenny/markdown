<?php

namespace Drupal\markdown\Annotation;

use Drupal\Component\Annotation\PluginID;

/**
 * Class MarkdownParser.
 *
 * @Annotation
 *
 * @Attributes(
 *   @Attribute(name = "id", type = "string", required = true),
 *   @Attribute(name = "checkClass", type = "string", required = true),
 * )
 */
class MarkdownParser extends PluginID {

  /**
   * The class to check if the parser is available.
   *
   * @var string
   */
  protected $checkClass;

  /**
   * The human-readable label.
   *
   * @var string
   */
  protected $label;

}
