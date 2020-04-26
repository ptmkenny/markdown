<?php

namespace Drupal\markdown\Traits;

trait MarkdownStatesTrait {

  /**
   * Retrieves the ancestry of the extension in a form/render array.
   *
   * @param array $element
   *   The element where the #parents array resides.
   * @param array $parents
   *   Optional. Additional parents to add.
   * @param string $property
   *   Optional. The property used to retrieve the array parents.
   *
   * @return array
   *   The element's parents array.
   */
  protected static function getElementParents(array $element, array $parents = [], string $property = '#parents') {
    return array_merge($element[$property] ?? [], $parents);
  }

  /**
   * Retrieves a states selector to use based on the form/render array parents.
   *
   * This is really only useful if using the States API (or something similar)
   *
   * @param array $parents
   *   The parents of the element.
   * @param string $name
   *   Optional. The setting name to append to the selector.
   *
   * @return string
   *   The selector path of the extension.
   */
  protected static  function getSatesSelector(array $parents = [], $name = '') {
    if (isset($name)) {
      $parents[] = $name;
    }
    $selector = array_shift($parents);
    if ($parents) {
      $selector .= '[' . implode('][', $parents) . ']';
    }
    return $selector ? ':input[name="' . $selector . '"]' : '';
  }

}
