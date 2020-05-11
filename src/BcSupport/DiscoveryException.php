<?php

namespace Drupal\markdown\BcSupport;

/**
 * Exception thrown during discovery if the data is invalid.
 *
 * @deprecated in markdown:8.x-2.0 and is removed from markdown:3.0.0.
 *   Use \Drupal\Component\Discovery\DiscoveryException instead.
 *
 * @see https://www.drupal.org/project/markdown/issues/3103679
 */
class DiscoveryException extends \RuntimeException {
}
