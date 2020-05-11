<?php

namespace Drupal\markdown\PluginManager;

/**
 * Interface for the Markdown Extension Plugin Manager.
 *
 * @method mixed[] getDefinitions($includeBroken = TRUE)
 * @method \Drupal\markdown\Plugin\Markdown\ExtensionInterface createInstance($plugin_id, array $configuration = [])
 * @noinspection PhpFullyQualifiedNameUsageInspection
 */
interface ExtensionManagerInterface extends MarkdownPluginManagerInterface {
}
