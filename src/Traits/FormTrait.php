<?php

namespace Drupal\markdown\Traits;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Component\Render\MarkupInterface;
use Drupal\Component\Serialization\Json;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Symfony\Component\Serializer\NameConverter\CamelCaseToSnakeCaseNameConverter;

/**
 * Trait providing helpful methods when dealing with forms.
 */
trait FormTrait {

  /**
   * Adds a #states selector to an element.
   *
   * @param array $element
   *   An element to add the state to, passed by reference.
   * @param string $state
   *   The state that will be triggered.
   * @param string $name
   *   The name of the element used for conditions.
   * @param array $conditions
   *   The conditions of $name that trigger $state.
   * @param array $parents
   *   An array of parents.
   */
  public static function addElementState(array &$element, $state, $name, array $conditions, array $parents = NULL) {
    // If parents weren't provided, attempt to extract it from the element.
    if (!isset($parents)) {
      $parents = isset($element['#parents']) ? $element['#parents'] : [];
    }

    // Add the state.
    if ($selector = static::getElementSelector($name, $parents)) {
      if (!isset($element['#states'][$state][$selector])) {
        $element['#states'][$state][$selector] = [];
      }
      $element['#states'][$state][$selector] = NestedArray::mergeDeep($element['#states'][$state][$selector], $conditions);
    }
  }

  /**
   * Adds a data attribute to an element.
   *
   * @param array $element
   *   An element, passed by reference.
   * @param string $name
   *   The name of the data attribute.
   * @param mixed $value
   *   The value of the data attribute. Note: do not JSON encode this value.
   */
  public static function addDataAttribute(array &$element, $name, $value) {
    static::addDataAttributes($element, [$name => $value]);
  }

  /**
   * Adds multiple data attributes to an element.
   *
   * @param array $element
   *   An element, passed by reference.
   * @param array $data
   *   The data attributes to add.
   */
  public static function addDataAttributes(array &$element, array $data) {
    $converter = new CamelCaseToSnakeCaseNameConverter();
    foreach ($data as $name => $value) {
      $name = str_replace('_', '-', $converter->normalize(preg_replace('/^data-?/', '', $name)));
      $element['#attributes']['data-' . $name] = is_string($value) || $value instanceof MarkupInterface ? (string) $value : Json::encode($value);
    }
  }

  /**
   * Creates an element, adding data attributes to it if necessary.
   *
   * @param array $element
   *   An element.
   *
   * @return array
   *   The modified $element.
   */
  public static function createElement(array $element) {
    if (isset($element['#attributes']['data']) && is_array($element['#attributes']['data'])) {
      static::addDataAttributes($element, $element['#attributes']['data']);
      unset($element['#attributes']['data']);
    }
    return $element;
  }

  /**
   * Retrieves the selector for an element.
   *
   * @param string $name
   *   The name of the element.
   * @param array $parents
   *   An array of parents.
   *
   * @return string
   *   The selector for an element.
   */
  public static function getElementSelector($name, array $parents) {
    // Immediately return if name is already an input selector.
    if (strpos($name, ':input[name="') === 0) {
      return $name;
    }

    // Add the name of the element that will be used for the condition.
    $parents[] = $name;

    // Remove the first parent as the base selector.
    $selector = array_shift($parents);

    // Join remaining parents with [].
    if ($parents) {
      $selector .= '[' . implode('][', $parents) . ']';
    }

    return $selector ? ':input[name="' . $selector . '"]' : '';
  }

  public static function resetToDefault(array &$element, $name, $defaultValue, FormStateInterface $form_state) {
    /** @var \Drupal\markdown\Form\SubformStateInterface $form_state */
    $selector = static::getElementSelector($name, $form_state->createParents());

    $reset = FormTrait::createElement([
      '#type' => 'link',
      '#title' => '↩️',
      '#url' => Url::fromUserInput('#reset-default-value', ['external' => TRUE]),
      '#attributes' => [
        'data' => [
          'markdownElement' => 'reset',
          'markdownId' => 'reset_' . $name,
          'markdownTarget' => $selector,
          'markdownDefaultValue' => $defaultValue,
        ],
        'title' => t('Reset to default value'),
        'style' => 'display: none; text-decoration: none;',
      ],
    ]);

    /** @var \Drupal\Core\Render\RendererInterface $renderer */
    $renderer = \Drupal::service('renderer');

    $element['#attached']['library'][] = 'markdown/reset';
    $element['#title'] = new FormattableMarkup('@title @reset', [
      '@title' => $element['#title'],
      '@reset' => $renderer->renderPlain($reset),
    ]);

  }

}
