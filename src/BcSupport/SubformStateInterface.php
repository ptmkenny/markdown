<?php

namespace Drupal\markdown\BcSupport;

use Drupal\Core\Form\FormStateInterface;

/**
 * Stores information about the state of a subform.
 *
 * @deprecated in markdown:8.x-2.0 and is removed from markdown:3.0.0.
 *   Use \Drupal\Core\Form\SubformStateInterface instead.
 *
 * @see https://www.drupal.org/project/markdown/issues/3103679
 */
interface SubformStateInterface extends FormStateInterface {

  /**
   * Gets the complete form state.
   *
   * @return \Drupal\Core\Form\FormStateInterface
   *   The complete form state.
   */
  public function getCompleteFormState();

}
