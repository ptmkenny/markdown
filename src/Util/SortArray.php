<?php

namespace Drupal\markdown\Util;

use Drupal\Component\Utility\SortArray as CoreSortArray;

/**
 * Array sorting helper methods.
 */
class SortArray extends CoreSortArray {

  /**
   * Recursively sorts an array by key.
   *
   * @param array $array
   *   An array to sort, passed by reference.
   */
  public static function recursiveKeySort(array &$array) {
    // First, sort the main array.
    ksort($array);

    // Then check for child arrays.
    foreach ($array as $key => &$value) {
      if (is_array($value)) {
        static::recursiveKeySort($value);
      }
    }
  }

}
