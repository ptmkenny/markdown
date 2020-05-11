<?php

namespace Drupal\markdown\Plugin\Markdown;

use Drupal\markdown\PluginManager\ExtensionCollection;
use Drupal\markdown\Util\SortArray;

/**
 * Base class for extensible markdown parsers.
 */
abstract class BaseExtensibleParser extends BaseParser implements ExtensibleParserInterface {

  /**
   * The extension configuration.
   *
   * @var array
   */
  protected $extensions = [];

  /**
   * A collection of MarkdownExtension plugins specific to the parser.
   *
   * @var \Drupal\markdown\PluginManager\ExtensionCollection
   */
  protected $extensionCollection;

  /**
   * {@inheritdoc}
   */
  public function getBundledExtensionIds() {
    return isset($this->pluginDefinition['bundledExtensions']) ? $this->pluginDefinition['bundledExtensions'] : [];
  }

  /**
   * {@inheritdoc}
   */
  public function getConfiguration() {
    $configuration = parent::getConfiguration();

    // Normalize extensions and their settings.
    $extensions = [];
    $extensionCollection = $this->extensions();
    /** @var \Drupal\markdown\Plugin\Markdown\ExtensionInterface $extension */
    foreach ($extensionCollection as $extensionId => $extension) {
      // Check whether extension is required by another enabled extension.
      $required = FALSE;
      if ($requiredBy = $extension->requiredBy()) {
        foreach ($requiredBy as $dependent) {
          if ($extensionCollection->get($dependent)->isEnabled()) {
            $required = TRUE;
            break;
          }
        }
      }

      // Skip disabled extensions that aren't required.
      if (!$required && !$extension->isEnabled()) {
        continue;
      }

      $extensions[] = $extension->getConfiguration();
    }

    // Only add extensions if there are extensions to save.
    if (!empty($extensions)) {
      // Sort extensions so they're always in the same order.
      uasort($extensions, function ($a, $b) {
        return SortArray::sortByKeyString($a, $b, 'id');
      });
      $configuration['extensions'] = array_values($extensions);
    }

    return $configuration;
  }

  /**
   * {@inheritdoc}
   */
  public function extension($extensionId) {
    return $this->extensions()->get($extensionId);
  }

  /**
   * {@inheritdoc}
   */
  public function extensionInterfaces() {
    return isset($this->pluginDefinition['extensionInterfaces']) ? $this->pluginDefinition['extensionInterfaces'] : [];
  }

  /**
   * {@inheritdoc}
   */
  public function extensions() {
    if (!isset($this->extensionCollection)) {
      $this->extensionCollection = new ExtensionCollection($this->getContainer()->get('plugin.manager.markdown.extension'), $this);
    }
    return $this->extensionCollection;
  }

  /**
   * {@inheritdoc}
   */
  public function getPluginCollections() {
    return ['extensions' => $this->extensions()];
  }

  /**
   * Sets the configuration for an extension plugin instance.
   *
   * @param string $extensionId
   *   The identifier of the extension plugin to set the configuration for.
   * @param array $configuration
   *   The extension plugin configuration to set.
   *
   * @return static
   *
   * @todo Actually use this.
   */
  public function setExtensionConfig($extensionId, array $configuration) {
    $this->extensions[$extensionId] = $configuration;
    if (isset($this->extensionCollection)) {
      $this->extensionCollection->setInstanceConfiguration($extensionId, $configuration);
    }
    return $this;
  }

}
