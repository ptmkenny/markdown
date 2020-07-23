<?php

namespace Drupal\markdown\PluginManager;

/**
 * Interface for the Markdown Parser Plugin Manager.
 *
 * @method \Drupal\markdown\Plugin\Markdown\ParserInterface[] all(array $configuration = [], $includeFallback = FALSE) : array
 * @method \Drupal\markdown\Plugin\Markdown\ParserInterface createInstance($plugin_id, array $configuration = [])
 * @method \Drupal\markdown\Annotation\MarkdownParser getDefinition($plugin_id, $exception_on_invalid = TRUE)
 * @method \Drupal\markdown\Annotation\MarkdownParser|void getDefinitionByClassName($className)
 * @method \Drupal\markdown\Annotation\MarkdownParser[] getDefinitions($includeFallback = TRUE)
 * @method string getFallbackPluginId($plugin_id = NULL, array $configuration = [])
 * @method \Drupal\markdown\Plugin\Markdown\ParserInterface[] installed(array $configuration = []) : array
 */
interface ParserManagerInterface extends InstallablePluginManagerInterface {
}
