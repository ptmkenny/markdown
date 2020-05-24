<?php

namespace Drupal\markdown\PluginManager;

use Composer\Semver\Semver;
use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\markdown\BcSupport\DiscoveryException;
use Drupal\markdown\Exception\MarkdownVersionException;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Base Markdown Plugin Manager.
 */
abstract class BasePluginManager extends DefaultPluginManager implements MarkdownPluginManagerInterface {

  use ContainerAwareTrait;
  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  protected function findDefinitions() {
    $definitions = parent::findDefinitions();

    // Normalize any callbacks provided.
    $this->normalizeArrayCallbacks($definitions);

    $this->sortDefinitions($definitions);
    return $definitions;
  }

  /**
   * Normalizes any callbacks provided so they can be stored in the database.
   *
   * @param array $array
   *   An array, passed by reference.
   * @param array $parents
   *   Recursion history, internal use only. Do not use.
   *
   * @return array
   *   The normalized array.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   *   When a callback provided isn't callable.
   */
  protected function normalizeArrayCallbacks(array &$array, array $parents = []) {
    foreach ($array as $key => $value) {
      if (($callable = is_callable($value)) || (is_array($value) && isset($value[0]) && isset($value[1]) && (is_object($value[0]) || (is_string($value[0]) && strpos($value[0], '\\') !== FALSE)) && is_string($value[1]))) {
        if (is_array($value)) {
          list($class, $method) = $value;
          if (is_object($class)) {
            $class = get_class($class);
          }
          try {
            $value = "$class::$method";
            $ref = new \ReflectionMethod($class, $method);
            $callable = $ref->isPublic() && $ref->isStatic();
          }
          catch (\ReflectionException $e) {
            // Intentionally do nothing.
          }
        }
        if (!$callable) {
          $plugin_id = current(array_slice($parents, 0, 1));
          $annotation = array_reverse(explode('\\', $this->pluginDefinitionAnnotationName))[0];
          $name = implode('.', $parents) . ".$key";
          throw new InvalidPluginDefinitionException($plugin_id, sprintf('The @%s callback "%s" set at %s is not callable.', $annotation, $value, $name), 0, isset($e) ? $e : NULL);
        }
      }
      if (is_array($value)) {
        $array[$key] = $this->normalizeArrayCallbacks($value, array_merge($parents, [$key]));
      }
    }
    return $array;
  }

  /**
   * {@inheritdoc}
   */
  public function all(array $configuration = [], $includeBroken = FALSE) {
    return array_map(function (array $definition) use ($configuration) {
      return $this->createInstance($definition['id'], isset($configuration[$definition['id']]) ? $configuration[$definition['id']] : $configuration);
    }, $this->getDefinitions($includeBroken));
  }

  /**
   * {@inheritdoc}
   *
   * @return \Drupal\markdown\Plugin\Markdown\PluginInterface
   *   Markdown plugin.
   */
  public function createInstance($plugin_id, array $configuration = []) {
    /** @var \Drupal\markdown\Plugin\Markdown\PluginInterface $instance */
    $instance = parent::createInstance($plugin_id, $configuration);
    if ($instance instanceof ContainerAwareInterface) {
      $instance->setContainer($this->getContainer());
    }
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function firstInstalledPluginId() {
    return current(array_keys($this->installedDefinitions())) ?: $this->getFallbackPluginId();
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
  public function getDefinitionByClassName($className) {
    $className = $this->normalizeClassName($className);
    $definitions = array_column($this->getDefinitions(), NULL, 'class');
    return isset($definitions[$className]) ? $definitions[$className] : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getDefinitions($includeBroken = TRUE) {
    $definitions = parent::getDefinitions();
    if ($includeBroken) {
      return $definitions;
    }
    unset($definitions[$this->getFallbackPluginId()]);
    return $definitions;
  }

  /**
   * {@inheritdoc}
   */
  abstract public function getFallbackPluginId($plugin_id = NULL, array $configuration = []);

  /**
   * {@inheritdoc}
   */
  public function installed(array $configuration = []) {
    return array_map(function (array $definition) use ($configuration) {
      return $this->createInstance($definition['id'], isset($configuration[$definition['id']]) ? $configuration[$definition['id']] : $configuration);
    }, $this->installedDefinitions());
  }

  /**
   * {@inheritdoc}
   */
  public function labels($installed = TRUE, $version = TRUE) {
    $labels = [];
    $parsers = $installed ? $this->installed() : $this->all();
    foreach ($parsers as $id => $parser) {
      // Cast to string for Drupal 7.
      /* @noinspection PhpMethodParametersCountMismatchInspection */
      $labels[$id] = (string) $parser->getLabel($version);
    }
    return $labels;
  }

  /**
   * {@inheritdoc}
   */
  public function installedDefinitions() {
    return array_filter($this->getDefinitions(FALSE), function (array $definition) {
      return $definition['id'] !== $this->getFallbackPluginId() && !empty($definition['installed']);
    });
  }

  /**
   * Normalizes class names to prevent double escaping.
   *
   * @param string $className
   *   The class name to normalize.
   *
   * @return string
   *   The normalized classname.
   */
  protected function normalizeClassName($className) {
    return is_string($className) ? ltrim(str_replace('\\\\', '\\', $className), '\\') : $className;
  }

  /**
   * {@inheritdoc}
   */
  public function processDefinition(&$definition, $pluginId) {
    if (!is_array($definition) || !($class = isset($definition['class']) ? $definition['class'] : NULL)) {
      return;
    }

    $installs = &$this->processDefinitionInstalls($definition, $pluginId);
    foreach ($installs as $installClass => $install) {
      $install['installed'] = class_exists($installClass) || interface_exists($installClass) || function_exists($installClass);
      $install = NestedArray::mergeDeep($definition, $install);

      // Provide the default preferred URL even if none are installed.
      if (!isset($definition['url']) && !empty($install['preferred']) && !empty($install['url'])) {
        $definition['url'] = $install['url'];
      }

      // Process the install for any relevant version information.
      try {
        $this->processDefinitionVersion($install, $pluginId);
      }
      // If there was a version exception, then it's not installed.
      catch (MarkdownVersionException $exception) {
        $install['installed'] = FALSE;
        $definition['versionExceptions'][$installClass] = $exception->getMessage();
      }

      // If the validating the version failed, then skip this install.
      if (empty($install['installed'])) {
        continue;
      }

      // Version validation passed, use this install.
      $definition = $install;
      break;
    }

    // Ensure the URL property is provided if there are supported installs.
    if (!empty($definition['installs']) && empty($definition['url'])) {
      throw new DiscoveryException('The "url" property must be provided.');
    }
  }

  /**
   * Performs extra processing on plugin definitions to normalize install data.
   *
   * @param array $definition
   *   The definition being processed.
   * @param string $pluginId
   *   The plugin identifier.
   *
   * @return array
   *   The installs array, passed by reference.
   */
  protected function &processDefinitionInstalls(array &$definition, $pluginId) {
    $definition['installs'] = [];
    $definition['installedClass'] = NULL;

    // Determine if plugin is installed.
    if (!isset($definition['installed'])) {
      $definition['installed'] = $this->providerExists($definition['provider']);
    }
    elseif (!is_bool($definition['installed'])) {
      $installed = (array) $definition['installed'];
      $preferred = FALSE;
      foreach ($installed as $key => $value) {
        $installClass = $this->normalizeClassName(is_string($key) && strpos($key, '\\') !== FALSE ? $key : $value);
        $definition['installs'][$installClass] = is_array($value) ? $value : [];
        if (!$preferred && !empty($definition['installs'][$installClass]['preferred'])) {
          $preferred = $installClass;
        }
        $definition['installs'][$installClass]['installedClass'] = $installClass;
      }
      if (!$preferred) {
        $preferred = current(array_keys($definition['installs']));
      }
      $definition['installs'][$preferred]['preferred'] = TRUE;
      $definition['installed'] = FALSE;
    }

    return $definition['installs'];
  }

  /**
   * Performs extra processing on plugin definitions to check installed version.
   *
   * @param array $definition
   *   The definition being processed.
   * @param string $pluginId
   *   The plugin identifier.
   */
  protected function processDefinitionVersion(array &$definition, $pluginId) {
    // Everything should resolve to a boolean.
    if (!is_bool($definition['installed'])) {
      throw new DiscoveryException('The "installed" property must either be a class name, an array of class names that are checked for existence or a boolean.');
    }

    // Return if plugin isn't installed.
    if (isset($definition['installed']) && empty($definition['installed'])) {
      return;
    }

    // Determine if plugin version, if not explicitly specified.
    if (isset($definition['version']) && is_string($definition['version'])) {
      $definition['versionClass'] = $this->normalizeClassName($definition['version']);
      if (defined($definition['versionClass'])) {
        $definition['version'] = constant($definition['versionClass']);
      }
      elseif (is_callable($definition['versionClass'])) {
        try {
          $definition['version'] = $definition['versionClass']();
        }
        // If there was a version exception, then it's not installed.
        catch (MarkdownVersionException $exception) {
          $definition['installed'] = FALSE;
          $definition['versionException'] = $exception->getMessage();
        }
      }
      else {
        throw new DiscoveryException('The "version" property must either be a constant or public class constant or property that exists in code somewhere. If complex requirements are needed, use the "versionConstraint" property.');
      }
    }

    // Handle version constraint.
    if (!empty($definition['versionConstraint'])) {
      if (empty($definition['version']) || !preg_match('/^(\d+\.\d+(?:\.\d+)?)/', $definition['version'], $matches)) {
        throw new MarkdownVersionException('Unknown version installed.');
      }

      // Satisfy the version constraint.
      if (!Semver::satisfies($matches[1], $definition['versionConstraint'])) {
        throw new MarkdownVersionException(sprintf('Installed version (%s) does not satisfy requirements. Please install a version matching %s.', $definition['version'], $definition['versionConstraint']));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function providerExists($provider) {
    // It's known that plugins provided by this module exist. Explicitly and
    // always return TRUE for this case. This is needed during install when
    // the module is not yet (officially) installed.
    // @see markdown_requirements()
    if ($provider === 'markdown') {
      return TRUE;
    }
    return parent::providerExists($provider);
  }

  /**
   * Sorts a definitions array.
   *
   * This sorts the definitions array first by the weight column, and then by
   * the plugin label, ensuring a stable, deterministic, and testable ordering
   * of plugins.
   *
   * @param array $definitions
   *   The definitions array to sort.
   *
   * @noinspection PhpParameterByRefIsNotUsedAsReferenceInspection
   */
  protected function sortDefinitions(array &$definitions) {
    $labels = array_map(function ($label) {
      return preg_replace("/[^a-z0-9]/", '', strtolower($label));
    }, array_column($definitions, 'label', 'id'));
    $weights = array_column($definitions, 'weight', 'id');
    if ($weights) {
      array_multisort($weights, SORT_NUMERIC, $labels, SORT_NATURAL, $definitions);
    }
    else {
      array_multisort($labels, SORT_NATURAL, $definitions);
    }
  }

}
