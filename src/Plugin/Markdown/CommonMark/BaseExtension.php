<?php

namespace Drupal\markdown\Plugin\Markdown\CommonMark;

use Drupal\markdown\Plugin\Markdown\BaseExtension as MarkdownBaseExtension;

/**
 * Base CommonMark Extension.
 *
 * @method \League\CommonMark\Extension\ExtensionInterface instantiateInstalledClass($args = NULL, $_ = NULL)
 */
abstract class BaseExtension extends MarkdownBaseExtension implements ExtensionInterface {
}
