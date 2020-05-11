<?php

namespace Drupal\markdown\PluginManager;

use Drupal\Core\Plugin\DefaultLazyPluginCollection;
use Drupal\markdown\Plugin\Markdown\ExtensibleParserInterface;

/**
 * Collection of extension plugins based on relevant parser.
 *
 * @property \Drupal\markdown\PluginManager\ExtensionManager $manager
 */
class ExtensionCollection extends DefaultLazyPluginCollection {

  /**
   * The Markdown Parser instance this extension collection belongs to.
   *
   * @var \Drupal\markdown\Plugin\Markdown\ExtensibleParserInterface
   */
  protected $parser;

  /**
   * ExtensionCollection constructor.
   *
   * @param \Drupal\markdown\PluginManager\ExtensionManagerInterface $manager
   *   The Markdown Extension Plugin Manager service.
   * @param \Drupal\markdown\Plugin\Markdown\ExtensibleParserInterface $parser
   *   A markdown parser instance.
   */
  public function __construct(ExtensionManagerInterface $manager, ExtensibleParserInterface $parser) {
    $extensionInterfaces = $parser->extensionInterfaces();

    // Filter out extensions that the parser doesn't support.
    $definitions = array_filter($manager->getDefinitions(FALSE), function ($definition) use ($extensionInterfaces) {
      $supported = FALSE;
      foreach ($extensionInterfaces as $interface) {
        if (is_subclass_of($definition['class'], $interface)) {
          $supported = TRUE;
          break;
        }
      }
      return $supported;
    });

    // Process passed configurations with known extension definitions.
    $configurations = $parser->config()->get('extensions') ?: [];
    foreach ($configurations as $key => &$configuration) {
      if (isset($definitions[$key])) {
        // Ensure the plugin key is set in the configuration.
        $configuration[$this->pluginKey] = $key;
        continue;
      }
      // Configuration defined a plugin key, use it.
      elseif (isset($configuration[$this->pluginKey])) {
        $configurations[$configuration[$this->pluginKey]] = $configuration;
      }

      // Remove unknown definition.
      unset($configurations[$key]);
    }

    // Ensure required dependencies are enabled.
    foreach ($definitions as $pluginId => $definition) {
      if (!empty($definition['requiredBy'])) {
        foreach ($definition['requiredBy'] as $dependent) {
          if (isset($configurations[$dependent]) && (!isset($configurations[$dependent]['enabled']) || !empty($configurations[$dependent]['enabled']))) {
            if (!isset($configurations[$pluginId])) {
              $configurations[$pluginId] = ['id' => $pluginId];
            }
            $configurations[$pluginId]['enabled'] = TRUE;
            break;
          }
        }
      }
    }

    // Fill in missing definitions.
    $pluginIds = array_keys($definitions);
    $configurations += array_combine($pluginIds, array_map(function ($pluginId) {
      return [$this->pluginKey => $pluginId];
    }, $pluginIds));

    // Sort configurations by using the keys of the already sorted definitions.
    $configurations = array_replace(array_flip(array_keys(array_intersect_key($definitions, $configurations))), $configurations);

    parent::__construct($manager, $configurations);
  }

  /**
   * {@inheritdoc}
   */
  public function sort() {
    // Intentionally do nothing, it's already sorted.
    return $this;
  }

}
