<?php

namespace Drupal\markdown\Config;

use Drupal\Core\Config\ImmutableConfigException;

/**
 * Immutable Markdown Config.
 *
 * @deprecated in markdown:8.x-2.0 and is removed from markdown:3.0.0.
 *   Use \Drupal\markdown\Form\ParserConfigurationForm instead.
 * @see https://www.drupal.org/project/markdown/issues/3142418
 */
class ImmutableMarkdownConfig extends MarkdownConfig {

  /**
   * {@inheritdoc}
   */
  public function set($key, $value) {
    throw new ImmutableConfigException("Can not set values on immutable configuration {$this->getName()}:$key. Use \\Drupal\\Core\\Config\\ConfigFactoryInterface::getEditable() to retrieve a mutable configuration object");
  }

  /**
   * {@inheritdoc}
   */
  public function clear($key) {
    throw new ImmutableConfigException("Can not clear $key key in immutable configuration {$this->getName()}. Use \\Drupal\\Core\\Config\\ConfigFactoryInterface::getEditable() to retrieve a mutable configuration object");
  }

  /**
   * {@inheritdoc}
   */
  public function save($has_trusted_data = FALSE) {
    throw new ImmutableConfigException("Can not save immutable configuration {$this->getName()}. Use \\Drupal\\Core\\Config\\ConfigFactoryInterface::getEditable() to retrieve a mutable configuration object");
  }

  /**
   * {@inheritdoc}
   */
  public function delete() {
    throw new ImmutableConfigException("Can not delete immutable configuration {$this->getName()}. Use \\Drupal\\Core\\Config\\ConfigFactoryInterface::getEditable() to retrieve a mutable configuration object");
  }

}
