<?php

namespace Drupal\markdown\Plugin\Markdown;

use Drupal\Component\Plugin\ConfigurableInterface;
use Drupal\Component\Plugin\DependentPluginInterface;
use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;

interface MarkdownPluginInterface extends ConfigurableInterface, ContainerAwareInterface, ContainerFactoryPluginInterface, DependentPluginInterface, PluginInspectionInterface {

  /**
   * Retrieves the config instance for this plugin.
   *
   * @return \Drupal\markdown\Config\ImmutableMarkdownConfig
   */
  public function config();

  /**
   * Retrieves the description of the plugin, if set.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup
   *   The description.
   */
  public function getDescription();

  /**
   * Displays the human-readable label of the plugin.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup
   *   The label.
   */
  public function getLabel();

  /**
   * Returns the provider (extension name) of the plugin.
   *
   * @return string
   *   The provider of the plugin.
   */
  public function getProvider();

  /**
   * Retrieves the URL of the plugin, if set.
   *
   * @return \Drupal\Core\Url|null
   */
  public function getUrl();

  /**
   * Returns the weight of the plugin (used for sorting).
   *
   * @return int
   *   The plugin weight.
   */
  public function getWeight();

}
