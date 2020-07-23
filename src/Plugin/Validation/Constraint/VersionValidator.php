<?php

namespace Drupal\markdown\Plugin\Validation\Constraint;

use Composer\Semver\Semver;
use Composer\Semver\VersionParser;
use Drupal\Core\Render\Markup;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Validates that a field is unique for the given entity type.
 *
 * @todo Move upstream to https://www.drupal.org/project/installable_plugins.
 * @internal
 */
class VersionValidator extends ConstraintValidator {

  /**
   * Semver version parser.
   *
   * @var \Composer\Semver\VersionParser
   */
  private static $versionParser;


  /**
   * {@inheritdoc}
   */
  public function validate($version, Constraint $constraint) {
    /** @var \Drupal\markdown\Plugin\Validation\Constraint\Version $constraint */
    $semverConstraints = $constraint->value;

    $message = $constraint->message;
    $params = [
      '@constraints' => Markup::create($semverConstraints),
      '@version' => Markup::create($version),
    ];
    $validated = FALSE;

    try {
      if (!empty($version)) {
        if (!empty($semverConstraints)) {
          $validated = Semver::satisfies($version, $semverConstraints);
        }
        else {
          if (!self::$versionParser) {
            self::$versionParser = new VersionParser();
          }
          $validated = !!self::$versionParser->normalize($version);
        }
      }
    }
    catch (\UnexpectedValueException $exception) {
      $message = $exception->getMessage();
    }

    if (!$validated) {
      $this->context->addViolation($message, $params);
    }
  }

}
