<?php

namespace Drupal\markdown\Annotation;

use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Link;
use Drupal\Core\Url;

/**
 * Trait for adding installable plugin properties to annotations.
 *
 * @internal
 *
 * @todo Move upstream to https://www.drupal.org/project/installable_plugins.
 */
trait InstallablePluginTrait {

  /**
   * The description of the plugin.
   *
   * @var \Drupal\Core\Annotation\Translation
   */
  public $description;

  /**
   * Indicates the plugin has been deprecated by providing a message.
   *
   * @var \Drupal\Core\Annotation\Translation
   */
  public $deprecated;

  /**
   * Indicates the plugin is experimental by providing a message.
   *
   * @var \Drupal\Core\Annotation\Translation
   */
  public $experimental;

  /**
   * Flag indicating whether plugin is installed.
   *
   * @var mixed
   *
   * @deprecated in markdown:8.x-2.0 and is removed from markdown:3.0.0.
   *   Use the "libraries" property instead.
   * @see https://www.drupal.org/project/markdown/issues/3142418
   */
  public $installed;

  /**
   * A human-readable label.
   *
   * @var \Drupal\Core\Annotation\Translation
   */
  public $label;

  /**
   * The class name of the primary object that is implemented by the library.
   *
   * Note: The following should automatically be prepended as a requirement for
   * the library when this property is set (where "<objectClassName>" is the
   * class name that was set):
   *
   *   @ InstallableRequirement(
   *     value = "<objectClassName>",
   *     constraints = {"Exists" = {}}
   *   ),
   *
   * @var string
   */
  public $object;

  /**
   * Flag indicating whether it is the preferred library.
   *
   * Note: if no libraries in a given plugin explicitly state they are the
   * preferred library or if multiple do, the first library will become
   * the preferred library.
   *
   * @var bool
   */
  public $preferred = FALSE;

  /**
   * An array of requirements for the plugin.
   *
   * @var \Drupal\markdown\Annotation\InstallableRequirement[]
   */
  public $requirements = [];

  /**
   * A list of requirement violation messages.
   *
   * @var string[]
   */
  public $requirementViolations = [];

  /**
   * An array of runtime requirements for the plugin.
   *
   * Note: this is primarily used internally to prevent recursion during
   * the discovery process of plugins. Typically this list is populated
   * automatically based on any provided $requirements set above. Instead of
   * using this property directly, use $requirements.
   *
   * @var \Drupal\markdown\Annotation\InstallableRequirement[]
   *
   * @internal
   */
  public $runtimeRequirements = [];

  /**
   * Flag indicating whether this plugin is to be visible in UI areas.
   *
   * Note: this is just a flag. It is up to whatever actually constructs the
   * UI to respect this value.
   *
   * @var bool
   */
  public $ui = TRUE;

  /**
   * A URL for the plugin, typically for installation instructions.
   *
   * @var string
   */
  public $url;

  /**
   * The installed version.
   *
   * Note: by default, if this property isn't explicitly set and the plugin
   * identifier contains a forwards slash (/), it will be treated as a
   * Composer vendor/package identifier and will be passed to
   * \Drupal\markdown\Util\Composer::getInstalledVersion to retrieve the
   * installed version. Otherwise, an explicit value must be passed here or
   * reference a defined constant or callable that will be invoked to use the
   * return value as its value.
   *
   * @var string
   *
   * @see \Drupal\markdown\Util\Composer::getInstalledVersion()
   */
  public $version;

  /**
   * The constraint the version must satisfy to be considered "installable".
   *
   * @var string
   *
   * @deprecated in markdown:8.x-2.0 and is removed from markdown:3.0.0.
   *   Use the "requirements" property instead.
   * @see https://www.drupal.org/project/markdown/issues/3142418
   */
  public $versionConstraint;

  /**
   * The weight of the plugin.
   *
   * @var int
   */
  public $weight = 0;

  /**
   * Retrieves the plugin as a link using its label and URL.
   *
   * @param string|\Drupal\Component\Render\MarkupInterface $label
   *   Optional. A specific label to use for the link. If not specified, it
   *   will default to the label or plugin identifier if present.
   * @param array $options
   *   An array of options to pass to the Url object constructor.
   * @param bool $fallback
   *   Flag indicating whether to fallback to the original label or plugin
   *   identifier if no link could be generated.
   *
   * @return \Drupal\Core\GeneratedLink|mixed|void
   *   The link if one was generated or the label if $fallback was provided.
   */
  public function getLink($label = NULL, array $options = [], $fallback = TRUE) {
    if (!isset($label)) {
      $label = $this->label ?: $this->id;
    }
    if ($url = $this->getUrl($options)) {
      return Link::fromTextAndUrl($label, $url)->toString();
    }
    elseif ($fallback) {
      return $label;
    }
  }

  /**
   * Retrieves the definition's URL property as an object.
   *
   * @param array $options
   *   An array of options to pass to the Url object constructor.
   *
   * @return \Drupal\Core\Url|void
   *   A Url object or NULL if no URL is set.
   */
  public function getUrl(array $options = []) {
    if ($url = $this->url) {
      if (UrlHelper::isExternal($url)) {
        if (!isset($options['attributes']['target'])) {
          $options['attributes']['target'] = '_blank';
        }
        return Url::fromUri($url, $options);
      }
      return Url::fromUserInput($url, $options);
    }
  }

  /**
   * Validates the plugin requirements.
   *
   * @param bool $runtime
   *   Flag indicating whether to validate runtime requirements.
   *
   * @return bool
   *   TRUE if requirements are met, FALSE if requirement violations exist.
   */
  public function validate($runtime = FALSE) {
    $requirements = $runtime ? $this->runtimeRequirements : $this->requirements;
    foreach ($requirements as $requirement) {
      /* @var \Symfony\Component\Validator\ConstraintViolationInterface $violation */
      foreach ($requirement->validate() as $violation) {
        $this->requirementViolations[] = $violation->getMessage();
        break 2;
      }
    }
    return empty($this->requirementViolations);
  }

}
