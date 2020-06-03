<?php

namespace Drupal\markdown\BcSupport;

if (!interface_exists('\Drupal\Core\Security\TrustedCallbackInterface')) {
  /* @noinspection PhpIgnoredClassAliasDeclaration */
  class_alias('\Drupal\markdown\BcSupport\BcAliasedInterface', '\Drupal\Core\Security\TrustedCallbackInterface');
}

use Drupal\Core\Security\TrustedCallbackInterface as CoreTrustedCallbackInterface;

/**
 * Interface to declare trusted callbacks.
 *
 * @deprecated in markdown:8.x-2.0 and is removed from markdown:3.0.0.
 *   Use \Drupal\Core\Security\TrustedCallbackInterface instead.
 * @see https://www.drupal.org/project/markdown/issues/3103679
 * @see \Drupal\markdown\BcSupport\DoTrustedCallbackTrait
 */
interface TrustedCallbackInterface extends CoreTrustedCallbackInterface {

  /**
   * Untrusted callbacks throw exceptions.
   */
  const THROW_EXCEPTION = 'exception';

  /**
   * Untrusted callbacks trigger silenced E_USER_DEPRECATION errors.
   */
  const TRIGGER_SILENCED_DEPRECATION = 'silenced_deprecation';

  /**
   * Untrusted callbacks trigger E_USER_WARNING errors.
   */
  const TRIGGER_WARNING = 'warning';

  /**
   * Lists the trusted callbacks provided by the implementing class.
   *
   * Trusted callbacks are public methods on the implementing class and can be
   * invoked via
   * \Drupal\markdown\BcSupport\DoTrustedCallbackTrait::doTrustedCallback().
   *
   * @return string[]
   *   List of method names implemented by the class that can be used as trusted
   *   callbacks.
   *
   * @see \Drupal\markdown\BcSupport\DoTrustedCallbackTrait::doTrustedCallback()
   */
  public static function trustedCallbacks();

}
