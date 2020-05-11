<?php

namespace Drupal\markdown\BcSupport;

/**
 * Provides an interface for an object using a plugin collection.
 *
 * @see \Drupal\Component\Plugin\LazyPluginCollection
 *
 * @deprecated in markdown:8.x-2.0 and is removed from markdown:3.0.0.
 *   Use \Drupal\Core\Plugin\ObjectWithPluginCollectionInterface instead.
 *
 * @see https://www.drupal.org/project/markdown/issues/3103679
 */
interface ObjectWithPluginCollectionInterface {

  /**
   * Gets the plugin collections used by this object.
   *
   * @return \Drupal\Component\Plugin\LazyPluginCollection[]
   *   An array of plugin collections, keyed by the property name they use to
   *   store their configuration.
   */
  public function getPluginCollections();

}
