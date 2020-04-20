<?php

namespace Drupal\markdown\Plugin\Markdown\Extension;

use Drupal\Core\Form\FormStateInterface;
use Drupal\markdown\Plugin\Filter\MarkdownFilterInterface;
use Drupal\markdown\Plugin\Markdown\MarkdownInstallablePluginInterface;

/**
 * Interface ExtensionInterface.
 */
interface MarkdownExtensionInterface extends MarkdownInstallablePluginInterface {

  /**
   * Retrieves the default settings.
   *
   * @return array
   *   The default settings.
   */
  public function defaultSettings();

  /**
   * Retrieves a setting.
   *
   * @param string $name
   *   The name of the setting to retrieve.
   *
   * @return mixed
   *   The settings value or NULL if not set.
   */
  public function getSetting($name);

  /**
   * Retrieves the current settings.
   *
   * @return array
   *   The settings array
   */
  public function getSettings();

  /**
   * Indicates whether the extension is being used.
   *
   * @return bool
   *   TRUE or FALSE
   */
  public function isEnabled();

  /**
   * Sets a specific setting.
   *
   * @param string $name
   *   The name of the setting to set.
   * @param mixed $value
   *   (optional) The value to set. If not provided it will be removed.
   */
  public function setSetting($name, $value = NULL);

  /**
   * Provides settings to an extension.
   *
   * @param array $settings
   *   The settings array.
   */
  public function setSettings(array $settings = []);

  /**
   * Returns the configuration form elements specific to this plugin.
   *
   * @param array $element
   *   The element render array for the extension configuration form.
   * @param \Drupal\Core\Form\FormStateInterface $formState
   *   The current state of the form.
   * @param \Drupal\markdown\Plugin\Filter\MarkdownFilterInterface $filter
   *   The filter this form belongs to.
   *
   * @return array
   *   The renderable form array representing the entire configuration form.
   */
  public function settingsForm(array $element, FormStateInterface $formState, MarkdownFilterInterface $filter);

}
