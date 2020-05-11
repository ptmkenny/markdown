<?php

namespace Drupal\markdown\PluginManager;

/**
 * Interface for the Markdown Parser Plugin Manager.
 *
 * @method mixed[] getDefinitions($includeBroken = TRUE)
 * @method string getFallbackPluginId($plugin_id = NULL, array $configuration = [])
 */
interface ParserManagerInterface extends MarkdownPluginManagerInterface {
}
