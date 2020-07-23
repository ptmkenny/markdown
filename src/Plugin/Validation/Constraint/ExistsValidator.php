<?php

namespace Drupal\markdown\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Validates that a class, interface, trait, or function exists.
 *
 * @todo Move upstream to https://www.drupal.org/project/installable_plugins.
 * @internal
 */
class ExistsValidator extends ConstraintValidator {

  public $name;

  /**
   * {@inheritdoc}
   */
  public function validate($class, Constraint $constraint) {
    if (!is_string($class) || empty($class) || (!class_exists($class) && !interface_exists($class) && !trait_exists($class) && !function_exists($class) && !defined($class) && !is_callable($class))) {
      $this->context->addViolation($constraint->message, [
        '@name' => isset($constraint->name) ? $constraint->name : $class,
      ]);
    }
  }

}
