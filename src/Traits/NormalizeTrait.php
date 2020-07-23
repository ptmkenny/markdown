<?php

namespace Drupal\markdown\Traits;

use Drupal\markdown\Exception\MarkdownUnexpectedValueException;

/**
 * Trait for providing normalization methods.
 *
 * @todo Move upstream to https://www.drupal.org/project/installable_plugins.
 */
trait NormalizeTrait {

  /**
   * Indicates whether a value is traversable.
   *
   * @param mixed $value
   *   The value to test.
   *
   * @return bool
   *   TRUE or FALSE
   */
  public static function isTraversable($value) {
    return is_array($value) || $value instanceof \Traversable;
  }

  /**
   * Normalizes any callables provided so they can be stored in the database.
   *
   * @param array|\Traversable $iterable
   *   An iterable value, passed by reference.
   * @param array $parents
   *   Internal use only. Keeps track of recursion history. DO NOT USE.
   *
   * @return array
   *   The normalized array.
   *
   * @throws \Drupal\markdown\Exception\MarkdownUnexpectedValueException
   *   When a callback provided isn't callable.
   */
  public static function normalizeCallables(&$iterable, array $parents = []) {
    // Immediately return if object isn't traversable.
    if (!static::isTraversable($iterable)) {
      return $iterable;
    }
    foreach ($iterable as $key => $value) {
      // Determine if the value is callable.
      if (($callable = is_callable($value)) || (is_array($value) && count($value) === 2 && isset($value[0]) && isset($value[1]) && (is_object($value[0]) || (is_string($value[0]) && strpos($value[0], '\\') !== FALSE)) && is_string($value[1]))) {
        if (is_array($value)) {
          list($class, $method) = $value;
          if (is_object($class)) {
            $class = get_class($class);
          }
          try {
            $value = "$class::$method";
            $ref = new \ReflectionMethod($class, $method);
            $callable = $ref->isPublic() && $ref->isStatic();
          }
          catch (\ReflectionException $e) {
            // Intentionally do nothing.
          }
        }
        if (!$callable) {
          throw new MarkdownUnexpectedValueException($value, $key, $parents, isset($e) ? $e : NULL, [
            'The callback "%s" is not publicly accessible.',
            'The callback "%s" set at %s is not publicly accessible.',
          ]);
        }
      }

      // Continue normalizing.
      $iterable[$key] = static::normalizeCallables($value, array_merge($parents, [$key]));
    }
    return $iterable;
  }

  /**
   * Normalizes class names to prevent double escaping.
   *
   * @param string|object $className
   *   The class name to normalize.
   *
   * @return string
   *   The normalized classname.
   */
  public static function normalizeClassName($className) {
    if (is_object($className)) {
      $className = get_class($className);
    }
    return is_string($className) ? ltrim(str_replace('\\\\', '\\', $className), '\\') : $className;
  }

}
