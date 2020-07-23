<?php

namespace Drupal\markdown\Plugin\Markdown\CommonMark;

use Drupal\markdown\Plugin\Markdown\BaseExtension as MarkdownBaseExtension;
use Drupal\markdown\Traits\ParserAwareTrait;

/**
 * Base CommonMark Extension.
 *
 * @property \Drupal\markdown\Annotation\MarkdownExtension $pluginDefinition
 * @method \Drupal\markdown\Annotation\MarkdownExtension getPluginDefinition()
 * @method \League\CommonMark\Extension\ExtensionInterface getObject($args = NULL, $_ = NULL)
 */
abstract class BaseExtension extends MarkdownBaseExtension implements ExtensionInterface {

  use ParserAwareTrait;

  /**
   * {@inheritdoc}
   */
  public function register($environment) {
    // Most plugins define the library object as the extension class that
    // represents the extension that should be registered with CommonMark.
    // This is added to the base class to assist with this common workflow.
    // Plugins can still override this method for advanced use cases as needed.
    $environment->addExtension($this->getObject());
  }

}
