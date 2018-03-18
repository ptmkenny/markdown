<?php

namespace Drupal\markdown\Plugin\Markdown\Extension;

use Drupal\markdown\Plugin\Markdown\Extension\BaseExtension;

/**
 * Base class for CommonMark extensions.
 */
abstract class CommonMarkExtension extends BaseExtension implements CommonMarkExtensionInterface {

  /**
   * {@inheritdoc}
   */
  public function getName() {
    $reflection = new \ReflectionClass($this);
    return $reflection->getShortName();
  }

}
