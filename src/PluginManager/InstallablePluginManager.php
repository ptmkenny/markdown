<?php

namespace Drupal\markdown\PluginManager;

use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\markdown\Annotation\InstallablePlugin;
use Drupal\markdown\Annotation\InstallableLibrary;
use Drupal\markdown\Annotation\InstallableRequirement;
use Drupal\markdown\Exception\MarkdownUnexpectedValueException;
use Drupal\markdown\Plugin\Markdown\InstallablePluginInterface;
use Drupal\markdown\Traits\NormalizeTrait;
use Drupal\markdown\Util\Composer;
use Drupal\markdown\Util\Semver;
use Drupal\markdown\Util\SortArray;
use Symfony\Cmf\Component\Routing\RouteObjectInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Installable Plugin Manager.
 *
 * @todo Move upstream to https://www.drupal.org/project/installable_plugins.
 */
abstract class InstallablePluginManager extends DefaultPluginManager implements InstallablePluginManagerInterface {

  use ContainerAwareTrait;
  use NormalizeTrait;
  use StringTranslationTrait;

  /**
   * The cached runtime definitions.
   *
   * @var array[]
   */
  protected static $runtimeDefinitions = [];

  /**
   * {@inheritdoc}
   */
  public function all(array $configuration = [], $includeFallback = FALSE) {
    $instances = array_map(function (InstallablePlugin $definition) use ($configuration) {
      $id = $definition->getId();
      return $this->createInstance($id, isset($configuration[$id]) ? $configuration[$id] : $configuration);
    }, $this->getDefinitions($includeFallback));

    uasort($instances, function (InstallablePluginInterface $a, InstallablePluginInterface $b) {
      $aWeight = $a->getWeight();
      $bWeight = $b->getWeight();
      if ($aWeight === $bWeight) {
        return 0;
      }
      return $aWeight < $bWeight ? -1 : 1;
    });

    return $instances;
  }

  /**
   * Allows plugin managers to further alter individual definitions.
   *
   * @param \Drupal\markdown\Annotation\InstallablePlugin $definition
   *   The definition being altered.
   * @param bool $runtime
   *   Flag indicating whether this is a runtime alteration.
   */
  protected function alterDefinition(InstallablePlugin $definition, $runtime = FALSE) {
  }

  /**
   * {@inheritdoc}
   */
  protected function alterDefinitions(&$definitions, $runtime = FALSE) {
    foreach ($definitions as $definition) {
      if ($definition instanceof InstallablePlugin) {
        $this->alterDefinition($definition, $runtime);
      }
    }
    if ($hook = $this->alterHook) {
      if ($runtime) {
        $hook = "_runtime";
      }
      $this->moduleHandler->alter($hook, $definitions);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function clearCachedDefinitions() {
    parent::clearCachedDefinitions();
    static::$runtimeDefinitions = [];
  }

  /**
   * Converts plugin definitions using the old "installed" method to libraries.
   *
   * @param \Drupal\markdown\Annotation\InstallablePlugin $plugin
   *   The definition being processed.
   *
   * @deprecated in markdown:8.x-2.0 and is removed from markdown:3.0.0.
   *   There is no replacement.
   * @see https://www.drupal.org/project/markdown/issues/3142418
   */
  protected function convertInstalledToLibraries(InstallablePlugin $plugin) {
    // Immediately return if "installed" isn't set.
    if (empty($installed = $plugin->installed)) {
      return;
    }

    $installs = [];
    foreach ((array) $plugin->installed as $key => $value) {
      $object = NULL;
      if ($value !== TRUE) {
        $object = static::normalizeClassName(is_string($key) && strpos($key,
          '\\') !== FALSE ? $key : $value);
        $installs[$object] = is_array($value) ? $value : [];
      }
    }
    foreach ($installs as $class => $definition) {
      $library = InstallableLibrary::create()->merge($definition);
      $library->object = $class;
      $plugin->libraries[] = $library;
    }
    unset($plugin->installed);

    // Retrieve the first library and merge any standalone properties on
    // the plugin.
    $library = reset($plugin->libraries);

    // Move URL property over to library.
    if (($url = $plugin->url) && !$library->url) {
      $library->url = $url;
      unset($plugin->url);
    }

    // Move version property over to library.
    if (($version = $plugin->version) && !$library->version) {
      $library->version = $version;
      unset($plugin->version);
    }

    // Move/convert versionConstraint into a requirement on library.
    if ($versionConstraint = $plugin->versionConstraint) {
      $requirement = new InstallableRequirement();
      $requirement->constraints['Version'] = $versionConstraint;
      $library->requirements[] = $requirement;
      unset($plugin->versionConstraint);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function createInstance($plugin_id, array $configuration = []) {
    /* @noinspection PhpUnhandledExceptionInspection */
    $instance = parent::createInstance($plugin_id, $configuration);
    if ($instance instanceof ContainerAwareInterface) {
      $instance->setContainer($this->getContainer());
    }
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  protected function findDefinitions() {
    $definitions = $this->getDiscovery()->getDefinitions();

    // If this plugin was provided by a Drupal extension that does not exist,
    // remove the plugin definition.
    /* @var \Drupal\markdown\Annotation\InstallablePlugin $definition */
    foreach ($definitions as $plugin_id => $definition) {
      if (($provider = $definition->getProvider()) && !in_array($provider, ['core', 'component']) && !$this->providerExists($provider)) {
        unset($definitions[$plugin_id]);
      }
    }

    foreach ($definitions as $plugin_id => &$definition) {
      $this->processDefinition($definition, $plugin_id);
    }

    $this->alterDefinitions($definitions);

    return $definitions;
  }

  /**
   * {@inheritdoc}
   */
  public function firstInstalledPluginId() {
    return current(array_keys($this->installedDefinitions())) ?: $this->getFallbackPluginId();
  }

  /**
   * {@inheritdoc}
   *
   * @param bool $runtime
   *   Flag indicating whether to retrieve runtime definitions.
   */
  protected function getCachedDefinitions($runtime = FALSE) {
    $cacheKey = $this->getCacheKey($runtime);
    if ($runtime) {
      if (!isset(static::$runtimeDefinitions[static::class]) && ($cache = $this->cacheGet($cacheKey))) {
        static::$runtimeDefinitions[static::class] = $cache->data;
      }
      return static::$runtimeDefinitions[static::class];
    }
    else {
      if (!isset($this->definitions) && ($cache = $this->cacheGet($cacheKey))) {
        $this->definitions = $cache->data;
      }
      return $this->definitions;
    }
  }

  /**
   * Retrieves the cache key to use.
   *
   * @param bool $runtime
   *   Flag indicating whether to retrieve runtime definitions.
   *
   * @return string
   *   The cache key.
   */
  public function getCacheKey($runtime = FALSE) {
    $cacheKey = $this->cacheKey;
    if ($runtime) {
      // Prematurely requesting the "active theme" causes the wrong theme
      // to be chosen due to the request not yet being fully populated with
      // the correct route object, or any for that matter.
      $request = \Drupal::request();
      if ($request->attributes->has(RouteObjectInterface::ROUTE_OBJECT)) {
        $cacheKey .= ':runtime:' . \Drupal::theme()->getActiveTheme()->getName();
      }
      else {
        $cacheKey .= ':runtime';
      }
    }
    return $cacheKey;
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
    $className = static::normalizeClassName($className);

    // Don't use array_column() here, PHP versions less than 7.0.0 don't work
    // as expected due to the fact that the definitions are objects.
    foreach ($this->getDefinitions() as $definition) {
      if ($definition->getClass() === $className) {
        return $definition;
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getDefinitions($includeFallback = TRUE) {
    $definitions = $this->getRuntimeDefinitions();
    if ($includeFallback) {
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
   * Retrieves the runtime definitions.
   *
   * @return \Drupal\markdown\Annotation\InstallablePlugin[]
   *   The runtime definitions.
   *
   * @noinspection PhpDocMissingThrowsInspection
   */
  protected function getRuntimeDefinitions() {
    // Ensure the class has an array key set, defaulted to NULL.
    if (!array_key_exists(static::class, static::$runtimeDefinitions)) {
      static::$runtimeDefinitions[static::class] = NULL;
    }

    // Retrieve cached runtime definitions.
    static::$runtimeDefinitions[static::class] = $this->getCachedDefinitions(TRUE);

    // Build the runtime definitions.
    if (!isset(static::$runtimeDefinitions[static::class])) {
      // Retrieve normal definitions.
      static::$runtimeDefinitions[static::class] = parent::getDefinitions();

      // Validate runtime definition requirements.
      /* @var \Drupal\markdown\Annotation\InstallablePlugin $definition */
      foreach (static::$runtimeDefinitions[static::class] as $definition) {
        $definition->validate(TRUE);
      }

      // Alter runtime definitions.
      $this->alterDefinitions(static::$runtimeDefinitions[static::class], TRUE);

      // Normalize any callbacks provided.
      try {
        static::normalizeCallables(static::$runtimeDefinitions[static::class]);
      }
      catch (MarkdownUnexpectedValueException $exception) {
        $plugin_id = array_reverse($exception->getParents())[0];
        $annotation = array_reverse(explode('\\', $this->pluginDefinitionAnnotationName))[0];
        throw new InvalidPluginDefinitionException($plugin_id, sprintf('Invalid callback defined in @%s. %s.', $annotation, $exception->getMessage()), 0, isset($e) ? $e : NULL);
      }

      // Re-validate runtime definition requirements after alterations.
      /* @var \Drupal\markdown\Annotation\InstallablePlugin $definition */
      foreach (static::$runtimeDefinitions[static::class] as $plugin_id => $definition) {
        $definition->validate(TRUE);
      }

      // Sort the runtime definitions.
      $this->sortDefinitions(static::$runtimeDefinitions[static::class]);

      // Cache the runtime definitions.
      $this->setCachedDefinitions(static::$runtimeDefinitions[static::class], TRUE);
    }

    // Runtime definitions should always be the active definitions.
    $this->definitions = static::$runtimeDefinitions[static::class];

    return $this->definitions;
  }

  /**
   * {@inheritdoc}
   */
  public function installed(array $configuration = []) {
    return array_map(function (InstallablePlugin $definition) use ($configuration) {
      $id = $definition->getId();
      return $this->createInstance($id, isset($configuration[$id]) ? $configuration[$id] : $configuration);
    }, $this->installedDefinitions());
  }

  /**
   * {@inheritdoc}
   */
  public function installedDefinitions() {
    return array_filter($this->getDefinitions(FALSE), function ($definition) {
      return $definition->getId() !== $this->getFallbackPluginId() && $definition->isInstalled();
    });
  }

  /**
   * {@inheritdoc}
   */
  public function labels($installed = TRUE, $version = TRUE) {
    $labels = [];
    $parsers = $installed ? $this->installed() : $this->all();
    foreach ($parsers as $id => $parser) {
      // Cast to string for Drupal 7.
      $labels[$id] = (string) $parser->getLabel($version);
    }
    return $labels;
  }

  /**
   * {@inheritdoc}
   */
  public function processDefinition(&$definition, $pluginId) {
    if (!($definition instanceof InstallablePlugin)) {
      return;
    }

    // Normalize the class.
    $definition->setClass(static::normalizeClassName($definition->getClass()));

    // Convert legacy "installed" property to "libraries".
    // @todo Deprecated functionality, remove before 3.0.0.
    $this->convertInstalledToLibraries($definition);

    // When no libraries or requirements are specified, create a new library
    // from the definition itself and treat it as its own standalone library.
    if (!$definition->libraries && !$definition->requirements && !$definition->runtimeRequirements && !$definition->requirementViolations) {
      $definition->libraries[] = InstallableLibrary::create($definition);
    }

    // Process libraries.
    $preferred = FALSE;
    $preferredWeight = -1;
    $seenIds = [];
    foreach ($definition->libraries as $key => $library) {
      $id = $library->getId();
      if (!isset($seenIds[$id])) {
        $seenIds[$id] = $library;
      }
      else {
        unset($definition->libraries[$key]);
      }
      /* @noinspection PhpUnhandledExceptionInspection */
      $this->processLibraryDefinition($definition, $library, $preferred);
      $preferredWeight = min($preferredWeight, $library->weight);
    }

    // If no library was preferred, default to the first library defined.
    if (!$preferred && ($library = reset($definition->libraries))) {
      $library->preferred = TRUE;
      $library->weight = $preferredWeight;
    }

    // Sort the library definitions.
    $this->sortDefinitions($definition->libraries);

    // Merge in the installed or preferred library into the actual plugin.
    if ($library = $definition->getInstalledLibrary() ?: $definition->getPreferredLibrary()) {
      // Merge library into plugin definition, excluding certain properties.
      $definition->merge($library, ['ui', 'weight']);

      // Set default URL for plugin based on the installed/preferred library.
      if (!$definition->url && $library->url) {
        $definition->url = $library->url;
      }
    }
  }

  /**
   * Processes the library definition.
   *
   * @param \Drupal\markdown\Annotation\InstallablePlugin $definition
   *   The plugin definition.
   * @param \Drupal\markdown\Annotation\InstallableLibrary $library
   *   A library definition.
   * @param bool $preferred
   *   A flag indicating whether a library was explicitly set as "preferred",
   *   passed by reference.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   */
  protected function processLibraryDefinition(InstallablePlugin $definition, InstallableLibrary $library, &$preferred = FALSE) {
    if (!$preferred && $library->preferred) {
      $preferred = TRUE;
    }

    // Prepend a new requirement to ensure that the object exists before
    // any other requirements are executed. This helps to ensure that if a
    // requirement depends on the object existing, it doesn't fatal and
    // instead treated as "uninstalled".
    if ($library->object) {
      $library->object = static::normalizeClassName($library->object);
      if ($requirement = $library->createObjectRequirement($definition)) {
        array_unshift($library->requirements, $requirement);
      }
    }

    // Convert versionConstraint into a requirement.
    // @todo Deprecated property, remove in 3.0.0.
    if ($versionConstraint = $library->versionConstraint) {
      $requirement = new InstallableRequirement();
      $requirement->constraints['Version'] = $versionConstraint;
      $library->requirements[] = $requirement;
      unset($library->versionConstraint);
    }

    $versionDefinition = NULL;

    // If version is populated with a callback or constant, add a requirement
    // that it should exist. Then, if the requirement is met, it will be
    // populated below with the validated value.
    if (!empty($library->version) && is_string($library->version) && !Semver::isValid($library->version)) {
      $versionDefinition = static::normalizeClassName($library->version);
      unset($library->version);
    }

    // Process requirements.
    $versionRequirement = NULL;
    if ($library->requirements) {
      foreach ($library->requirements as $key => $requirement) {
        // Version constraints that have not explicitly specified a value
        // or callback should be validated against this library's installed
        // version which can only be determined later below; save it.
        if (!isset($requirement->value) && !isset($requirement->callback) && count($requirement->constraints) === 1 && key($requirement->constraints) === 'Version' && !empty($requirement->constraints['Version'])) {
          $versionRequirement = $requirement;
          unset($library->requirements[$key]);
          continue;
        }

        // Move parser and extension requirements to runtime.
        // Note: this helps to prevent recursion while building definitions.
        if (in_array($requirement->getType(), ['parser', 'extension'], TRUE)) {
          $library->runtimeRequirements[] = $requirement;
          unset($library->requirements[$key]);
          continue;
        }

        foreach ($requirement->validate() as $violation) {
          $library->requirementViolations[] = $violation->getMessage();

          // Immediately stop validating if the requirement returns a
          // violation. This ensures that further requirements which may
          // depend on the previous ones passing don't fatal.
          break 2;
        }
      }
    }

    // Do not continue if there were requirement violations.
    if (!empty($library->requirementViolations)) {
      return;
    }

    // Now that requirements have been met, actually extract the version
    // from the definition that was provided.
    if (isset($versionDefinition)) {
      if (!$versionRequirement) {
        $versionRequirement = new InstallableRequirement();
        $versionRequirement->constraints = ['Version' => []];
      }
      if (defined($versionDefinition) && ($version = constant($versionDefinition))) {
        $versionRequirement->value = $version;
      }
      elseif (is_callable($versionDefinition) && ($version = call_user_func_array($versionDefinition, [$library, $definition]))) {
        $versionRequirement->value = $version;
      }
      elseif ($library->object && ($version = Composer::getVersionFromClass($library->object))) {
        $versionRequirement->value = $version;
      }
      else {
        throw new InvalidPluginDefinitionException($definition->getId(), 'The "version" property must either be a constant or public class constant or property that exists in code somewhere. If complex requirements are needed, add one using the "Version" constraint as a requirement.');
      }

      // Now, validate the version.
      if (!count($violations = $versionRequirement->validate())) {
        $library->version = $versionRequirement->value;
      }
      else {
        foreach ($violations as $violation) {
          $library->requirementViolations[] = $violation->getMessage();
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function setCacheBackend(CacheBackendInterface $cache_backend, $cache_key, array $cache_tags = []) {
    $cache_tags[] = $cache_key;
    $cache_tags[] = "$cache_key:runtime";
    /** @var \Drupal\Core\Extension\ThemeHandlerInterface $themeHandler */
    $themeHandler = \Drupal::service('theme_handler');
    foreach (array_keys($themeHandler->listInfo()) as $theme) {
      $cache_tags[] = "$cache_key:runtime:$theme";
    }
    parent::setCacheBackend($cache_backend, $cache_key, array_unique($cache_tags));
  }

  /**
   * Sets a cache of plugin definitions for the decorated discovery class.
   *
   * @param array $definitions
   *   List of definitions to store in cache.
   * @param bool $runtime
   *   Flag indicating whether this is setting runtime definitions.
   */
  protected function setCachedDefinitions($definitions, $runtime = FALSE) { // phpcs:ignore
    $cacheKey = $this->getCacheKey($runtime);
    $this->cacheSet($cacheKey, $definitions, Cache::PERMANENT, [$cacheKey]);
    if ($runtime) {
      static::$runtimeDefinitions[static::class] = $definitions;
    }
    else {
      $this->definitions = $definitions;
    }
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
   * @param array $properties
   *   Optional. The properties to sort by.
   */
  protected function sortDefinitions(array &$definitions, array $properties = ['weight', 'label']) {
    SortArray::multisortProperties($definitions, $properties);
  }

}
