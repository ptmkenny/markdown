<?php

namespace Drupal\markdown;

use Drupal\Component\Plugin\FallbackPluginManagerInterface;
use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;

interface MarkdownPluginManagerInterface extends ContainerAwareInterface, ContainerInjectionInterface, PluginManagerInterface, FallbackPluginManagerInterface {

  /**
   * Retrieves all registered plugins.
   *
   * @param bool $includeBroken
   *   Flag indicating whether to include the "_broken" fallback parser.
   *
   * @return array
   *   An array of plugins instances, keyed by plugin identifier.
   */
  public function all($includeBroken = FALSE): array;

  /**
   * Retrieves all installed MarkdownParser plugins.
   *
   * @param array $configuration
   *   The configuration used to create plugin instances.
   *
   * @return array
   *   An array of installed plugins instances, keyed by plugin identifier.
   */
  public function getInstalled(array $configuration = []): array;

  /**
   * Retrieves the labels for parsers.
   *
   * @param bool $installed
   *   Flag indicating whether to return just the installed parsers.
   * @param bool $version
   *   Flag indicating whether to include the version with the label.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup[]
   *   An array of labels, keyed by parser identifier.
   */
  public function getLabels($installed = TRUE, $version = TRUE): array;

}
