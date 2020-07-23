<?php

namespace Drupal\markdown\PluginManager;

/**
 * Base Markdown Plugin Manager.
 *
 * @method \Drupal\markdown\Annotation\InstallablePlugin[] getDefinitions($includeFallback = TRUE)
 *
 * @deprecated in markdown:8.x-2.0 and is removed from markdown:3.0.0.
 *   Use \Drupal\markdown\PluginManager\InstallablePluginManager instead.
 * @see https://www.drupal.org/project/markdown/issues/3142418
 */
abstract class BasePluginManager extends InstallablePluginManager implements MarkdownPluginManagerInterface {
}
