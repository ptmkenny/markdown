<?php

namespace Drupal\markdown\Plugin\Markdown;

use Drupal\Component\Utility\DiffArray;
use Drupal\Component\Utility\NestedArray;
use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Plugin\PluginBase as CoreBasePlugin;
use Drupal\Core\Plugin\PluginDependencyTrait;
use Drupal\Core\Url;
use Drupal\markdown\BcSupport\ObjectWithPluginCollectionInterface;
use Drupal\markdown\Config\ImmutableMarkdownConfig;
use Drupal\markdown\Traits\MoreInfoTrait;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Base Markdown Plugin.
 */
abstract class PluginBase extends CoreBasePlugin implements PluginInterface {

  use ContainerAwareTrait;
  use MoreInfoTrait;
  use PluginDependencyTrait;

  /**
   * The config for this plugin.
   *
   * @var \Drupal\markdown\Config\ImmutableMarkdownConfig
   */
  protected $config;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->setConfiguration($configuration);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'id' => $this->getPluginId(),
      'provider' => $this->getProvider(),
      'weight' => $this->getWeight(),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    $dependencies = [];
    if ($this instanceof ObjectWithPluginCollectionInterface) {
      foreach ($this->getPluginCollections() as $pluginCollection) {
        foreach ($pluginCollection as $instance) {
          $dependencies = NestedArray::mergeDeep($dependencies, $this->getPluginDependencies($instance));
        }
      }
    }
    return $dependencies;
  }

  /**
   * {@inheritdoc}
   */
  public function config() {
    return $this->config;
  }

  /**
   * Retrieves the config class used to construct settings.
   *
   * @return string
   *   The config class to use.
   */
  protected function getConfigClass() {
    return ImmutableMarkdownConfig::class;
  }

  /**
   * Retrieves the config type used to construct settings.
   *
   * @return string
   *   The config type to use.
   */
  abstract protected function getConfigType();

  /**
   * {@inheritdoc}
   */
  public function getConfiguration() {
    return [
      'id' => $this->getPluginId(),
    ];
  }

  /**
   * Retrieves the container.
   *
   * @return \Symfony\Component\DependencyInjection\ContainerInterface
   *   The container.
   */
  public function getContainer() {
    return $this->container instanceof ContainerInterface ? $this->container : \Drupal::getContainer();
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return isset($this->pluginDefinition['description']) ? $this->pluginDefinition['description'] : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getLabel() {
    return isset($this->pluginDefinition['label']) ? $this->pluginDefinition['label'] : $this->getPluginId();
  }

  /**
   * {@inheritdoc}
   */
  public function getProvider() {
    return $this->pluginDefinition['provider'];
  }

  /**
   * {@inheritdoc}
   */
  public function getUrl() {
    $url = !empty($this->pluginDefinition['url']) ? $this->pluginDefinition['url'] : NULL;
    if ($url && UrlHelper::isExternal($url)) {
      return Url::fromUri($url)->setOption('attributes', ['target' => '_blank']);
    }
    return $url ? Url::fromUserInput($url) : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getWeight() {
    return isset($this->pluginDefinition['weight']) ? (int) $this->pluginDefinition['weight'] : 0;
  }

  /**
   * {@inheritdoc}
   */
  public function setConfiguration(array $configuration) {
    if (($class = $this->getConfigClass()) && ltrim($class, '\\') !== ImmutableMarkdownConfig::class && !is_subclass_of($class, ImmutableMarkdownConfig::class)) {
      throw new \RuntimeException(sprintf('The class %s must be an instance of %s.', $class, ImmutableMarkdownConfig::class));
    }
    $defaultConfiguration = $this->defaultConfiguration();
    $defaultConfiguration['id'] = $this->getPluginId();
    $defaultConfiguration['provider'] = $this->getProvider();
    $defaultConfiguration['weight'] = $this->getWeight();
    $this->config = $class::create($this->getContainer(), $this->getConfigType(), $defaultConfiguration);
    $overrides = DiffArray::diffAssocRecursive($configuration, $defaultConfiguration);
    $this->config->setModuleOverride($overrides);
    return $this;
  }

}
