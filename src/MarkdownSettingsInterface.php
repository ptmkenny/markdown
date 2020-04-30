<?php

namespace Drupal\markdown;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;

interface MarkdownSettingsInterface extends ContainerInjectionInterface {

  /**
   * Retrieves the parser, using provided settings and configuration.
   *
   * @param array $configuration
   *   Optional. Provides the ability to override what is currently set.
   *
   * @return \Drupal\markdown\Plugin\Markdown\MarkdownParserInterface
   */
  public function getParser(array $configuration = []);

  /**
   * Retrieves the set parser identifier.
   *
   * @param bool $fallback
   *   Flag indicating whether to fallback to the first installed parser
   *   plugin identifier or the "_broken" identifier if none are installed
   *   and the config value is not set.
   *
   * @return string|null
   *   The parser identifier or NULL if not set.
   */
  public function getParserId($fallback = TRUE);

  /**
   * Retrieves the parser configuration to be used with constructing plugins.
   *
   * @param array $configuration
   *   Optional. Provides the ability to override what is currently set.
   *
   * @return array
   *   The parser configuration.
   */
  public function getParserConfiguration(array $configuration = []);

  /**
   * Retrieves a specific parser extension setting.
   *
   * @param string $extension
   *   The name of extension for which to retrieve settings for.
   * @param string $name
   *   The setting name.
   * @param mixed $default
   *   Optional. The default value to provide if $name isn't set.
   *
   * @return mixed
   */
  public function getParserExtensionSetting($extension, $name, $default = NULL);

  /**
   * Retrieves a specific parser extension settings.
   *
   * @param string $extension
   *   The name of extension for which to retrieve settings for.
   *
   * @return mixed
   */
  public function getParserExtensionSettings($extension);

  /**
   * Retrieves all parser extension settings.
   *
   * @param bool $keyed
   *   Flag indicating whether to return an associative array of parser
   *   extensions, keyed by parser extension identifier.
   *
   * @return array
   *   The parser extensions.
   */
  public function getParserExtensions($keyed = FALSE);

  /**
   * Retrieves a specific parser setting.
   *
   * @param string $name
   *   The setting name.
   * @param mixed $default
   *   Optional. The default value to provide if $name isn't set.
   *
   * @return mixed
   *   The parser setting or $default if $name isn't set.
   */
  public function getParserSetting($name, $default = NULL);

  /**
   * Retrieves the parser settings to be used with constructing plugins.
   *
   * @return array
   *   The parser settings.
   */
  public function getParserSettings();

}
