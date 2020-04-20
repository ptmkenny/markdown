<?php

namespace Drupal\markdown\Plugin\Markdown\Extension;

use Drupal\Component\Utility\NestedArray;
use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\PluginBase;
use Drupal\Core\Url;
use Drupal\markdown\Annotation\MarkdownExtension;
use Drupal\markdown\Plugin\Filter\MarkdownFilterInterface;
use Drupal\markdown\Traits\MarkdownStatesTrait;

/**
 * Base class for markdown extensions.
 *
 * @MarkdownExtension(
 *   id = "_broken",
 *   label = @Translation("Missing Extension"),
 * )
 */
class BaseExtension extends PluginBase implements MarkdownExtensionInterface {

  use MarkdownStatesTrait;

  /**
   * {@inheritdoc}
   */
  public static function installed(): bool {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public static function version() {
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->setConfiguration($configuration);
  }

  /**
   * Returns generic default configuration for markdown extension plugins.
   *
   * @return array
   *   An associative array with the default configuration.
   */
  protected function baseConfigurationDefaults() {
    return [
      'id' => $this->getPluginId(),
      'label' => $this->t('Broken'),
      'provider' => $this->pluginDefinition['provider'],
      'settings' => $this->defaultSettings() + ['enabled' => FALSE],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function defaultSettings() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getConfiguration() {
    return $this->configuration;
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->pluginDefinition['description'] ?? NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getLabel($version = TRUE) {
    $label = $this->pluginDefinition['label'] ?? $this->pluginId;
    if ($version && ($version = $this->getVersion())) {
      $label .= " ($version)";
    }
    return $label;
  }

  /**
   * {@inheritdoc}
   */
  public function getSetting($name) {
    $settings = $this->getSettings();
    return isset($settings[$name]) ? $settings[$name] : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getUrl() {
    $url = $this->pluginDefinition['url'] ?? NULL;
    if ($url && UrlHelper::isExternal($url)) {
      return Url::fromUri($url);
    }
    return $url ? Url::fromUserInput($url) : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getVersion() {
    return $this->pluginDefinition['version'] ?? NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function isEnabled() {
    return !!$this->getSetting('enabled');
  }

  /**
   * {@inheritdoc}
   */
  public function isInstalled(): bool {
    return $this->pluginDefinition['installed'] ?? FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function label() {
    return $this->configuration['label'] ?: $this->pluginId;
  }

  /**
   * {@inheritdoc}
   */
  public function setConfiguration(array $configuration) {
    $this->configuration = NestedArray::mergeDeep(
      $this->baseConfigurationDefaults(),
      $this->defaultConfiguration(),
      $configuration
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getSettings() {
    return $this->configuration['settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function setSetting($name, $value = NULL) {
    if (isset($value)) {
      // Get the type of the exist value (if any).
      if (isset($this->configuration['settings'][$name]) && ($type = gettype($this->configuration['settings'][$name]))) {
        $original_value = is_object($value) ? clone $value : $value;
        if (!settype($value, $type)) {
          $value = $original_value;
        }
      }
      $this->configuration['settings'][$name] = $value;
    }
    else {
      unset($this->configuration['settings'][$name]);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function setSettings(array $settings = []) {
    foreach ($settings as $name => $value) {
      $this->setSetting($name, $value);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $element, FormStateInterface $formState, MarkdownFilterInterface $filter) {
    $definition = $this->getPluginDefinition();
    $element['provider'] = [
      '#type' => 'value',
      '#value' => $definition['provider'],
    ];
    return $element;
  }

}
