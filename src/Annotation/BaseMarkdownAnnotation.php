<?php

namespace Drupal\markdown\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Base Markdown Plugin Annotation.
 */
abstract class BaseMarkdownAnnotation extends Plugin {

  /**
   * The parser identifier.
   *
   * @var string
   */
  protected $id;

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

  /**
   * Flag indicating whether plugin is installed.
   *
   * @var bool|string
   *   TRUE or FALSE
   */
  protected $installed;

  /**
   * The parser URL.
   *
   * @var string
   */
  protected $url;

  /**
   * The installed version.
   *
   * @var string
   */
  protected $version;

  /**
   * The constraint for which the provided version must satisfy.
   *
   * @var string
   */
  protected $versionConstraint;

  /**
   * The weight of the plugin.
   *
   * @var int
   */
  protected $weight = 0;

}
