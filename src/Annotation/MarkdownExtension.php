<?php

namespace Drupal\markdown\Annotation;

use Drupal\Component\Annotation\PluginID;

/**
 * Class CommonMarkExtension.
 *
 * @Annotation
 *
 * @Attributes(
 *   @Attribute(name = "id", type = "string", required = true),
 *   @Attribute(name = "parser", type = "string", required = true),
 * )
 */
class MarkdownExtension extends PluginID {

  /**
   * The id of a MarkdownParser annotated plugin this extension belongs to.
   *
   * @var string
   */
  protected $parser;

}
