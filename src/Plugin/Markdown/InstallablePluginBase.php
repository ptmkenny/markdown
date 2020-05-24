<?php

namespace Drupal\markdown\Plugin\Markdown;

use Drupal\Component\Utility\NestedArray;
use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Url;

/**
 * Base class for installable markdown plugins.
 */
abstract class InstallablePluginBase extends PluginBase implements InstallablePluginInterface {

  /**
   * {@inheritdoc}
   */
  public function getDeprecated() {
    return isset($this->pluginDefinition['deprecated']) ? $this->pluginDefinition['deprecated'] : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getInstalledClass() {
    return (string) ($this->isInstalled() && isset($this->pluginDefinition['installedClass']) ? $this->pluginDefinition['installedClass'] : '');
  }

  /**
   * {@inheritdoc}
   */
  public function getInstallationInstructions() {
    $pluginDefinition = $this->getPluginDefinition();
    if (!empty($pluginDefinition['installs'])) {
      $build = ['#type' => 'container'];
      $build['message'] = [
        '#markup' => $this->t('Choose one of the following for instructions on how to install:'),
      ];
      $build['options'] = [
        '#theme' => 'item_list',
        '#items' => [],
      ];
      foreach ($pluginDefinition['installs'] as $install) {
        $install = NestedArray::mergeDeep($pluginDefinition, $install);
        $url = UrlHelper::isExternal($install['url']) ? Url::fromUri($install['url'])->setOption('attributes', ['target' => '_blank']) : Url::fromUserInput($install['url']);
        $suffix = [];
        if (!empty($install['versionConstraint'])) {
          $suffix[] = $install['versionConstraint'];
        }
        if (!empty($install['preferred'])) {
          $suffix[] = $this->t('preferred');
        }
        $build['options']['#items'][] = [
          'data' => [
            '#type' => 'link',
            '#title' => $install['url'],
            '#url' => $url,
            '#suffix' => $suffix ? ' (' . implode(', ', $suffix) . ')' : NULL,
          ],
        ];
      }
      return $this->renderer()->renderPlain($build);
    }
  }

  /**
   * {@inheritdoc}
   *
   * @TODO: Refactor to use variadic parameters.
   */
  public function instantiateInstalledClass($args = NULL, $_ = NULL) {
    if ($class = $this->getInstalledClass()) {
      $ref = new \ReflectionClass($class);
      return $ref->newInstanceArgs(func_get_args());
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getLabel($version = TRUE) {
    $label = parent::getLabel();
    if ($version && ($version = $this->getVersion())) {
      $label .= " ($version)";
    }
    return $label;
  }

  /**
   * {@inheritdoc}
   */
  public function getPreferredInstallDefinition() {
    $installs = isset($this->pluginDefinition['installs']) ? $this->pluginDefinition['installs'] : [];
    foreach ($installs as $install) {
      if (!empty($install['preferred'])) {
        return NestedArray::mergeDeep($this->pluginDefinition, $install);
      }
    }
    return $this->pluginDefinition;
  }

  /**
   * {@inheritdoc}
   */
  public function getVersion() {
    return isset($this->pluginDefinition['version']) ? $this->pluginDefinition['version'] : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function hasMultipleInstalls() {
    return isset($this->pluginDefinition['installs']) ? count($this->pluginDefinition['installs']) > 1 : FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function isInstalled() {
    return isset($this->pluginDefinition['installed']) ? !!$this->pluginDefinition['installed'] : FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function isPreferredInstall() {
    // Immediately return if not installed.
    if (!($class = $this->getInstalledClass())) {
      return FALSE;
    }

    // Or has only one install.
    if (!$this->hasMultipleInstalls()) {
      return TRUE;
    }

    $preferred = $this->getPreferredInstallDefinition();

    return isset($preferred['installedClass']) ? $preferred['installedClass'] === $class : !!$class;
  }

}
