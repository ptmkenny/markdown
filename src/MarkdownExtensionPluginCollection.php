<?php

namespace Drupal\markdown;

use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Plugin\DefaultLazyPluginCollection;
use Drupal\markdown\Plugin\Markdown\ExtensibleMarkdownParserInterface;
use Drupal\markdown\Plugin\Markdown\Extension\MarkdownExtensionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @property \Drupal\markdown\MarkdownExtensionPluginManager $manager
 */
class MarkdownExtensionPluginCollection extends DefaultLazyPluginCollection implements ContainerInjectionInterface {

  /**
   * The Markdown Parser instance this extension collection belongs to.
   *
   * @var \Drupal\markdown\Plugin\Markdown\ExtensibleMarkdownParserInterface
   */
  protected $parser;

  /**
   * {@inheritdoc}
   */
  public function __construct(MarkdownExtensionPluginManagerInterface $manager, ExtensibleMarkdownParserInterface $parser) {
    $extensionInterfaces = $parser->extensionInterfaces();
    if (!$extensionInterfaces) {
      throw new InvalidPluginDefinitionException($parser->getPluginId(), 'Markdown parser must specify the extension interfaces it supports; none given.');
    }

    foreach ($extensionInterfaces as $interface) {
      if (ltrim($interface, '\\') === MarkdownExtensionInterface::class) {
        throw new InvalidPluginDefinitionException($parser->getPluginId(), sprintf('Markdown parser cannot specify %s as the extension interface. It must create a unique interface and extend from it.', MarkdownExtensionInterface::class));
      }
      if (!is_subclass_of(ltrim($interface, '\\'), MarkdownExtensionInterface::class)) {
        throw new InvalidPluginDefinitionException($parser->getPluginId(), sprintf('Markdown parser indicates that it supports the extension interface "%s", but this interface does not extend %s.', $interface, MarkdownExtensionInterface::class));
      }
    }

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
  public static function create(ContainerInterface $container, ExtensibleMarkdownParserInterface $parser = NULL) {
    if (!$parser) {
      throw new \RuntimeException('Markdown parser instance must be passed to create an extension collection.');
    }
    if (!$container) {
      $container = \Drupal::getContainer();
    }
    return new static(
      $container->get('plugin.manager.markdown.extension'),
      $parser
    );
  }

  /**
   * {@inheritdoc}
   */
  public function sort() {
    // Intentionally do nothing, it's already sorted.
    return $this;
  }

}
