<?php

namespace Drupal\markdown\BcSupport;

if (!class_exists('\Drupal\Core\Security\UntrustedCallbackException')) {
  /* @noinspection PhpIgnoredClassAliasDeclaration */
  class_alias('\RuntimeException', '\Drupal\Core\Security\UntrustedCallbackException');
}

use Drupal\Core\Security\UntrustedCallbackException as CoreUntrustedCallbackException;

/**
 * Exception thrown if a callback is untrusted.
 *
 * @deprecated in markdown:8.x-2.0 and is removed from markdown:3.0.0.
 *   Use \Drupal\Core\Security\UntrustedCallbackException instead.
 * @see https://www.drupal.org/project/markdown/issues/3103679
 */
class UntrustedCallbackException extends CoreUntrustedCallbackException {
}
