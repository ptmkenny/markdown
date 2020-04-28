<?php

namespace Drupal\markdown;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Config\Config;
use Drupal\Core\Config\StorageInterface;
use Drupal\Core\Config\TypedConfigManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class MarkdownSettings extends Config implements MarkdownSettingsInterface {

  /**
   * The Markdown Parser Plugin Manager service.
   *
   * @var \Drupal\markdown\MarkdownParserPluginManagerInterface
   */
  protected $parserManager;

  /**
   * {@inheritdoc}
   */
  public function __construct($name, StorageInterface $storage, EventDispatcherInterface $event_dispatcher, TypedConfigManagerInterface $typed_config, MarkdownParserPluginManagerInterface $parserManager, array $data = NULL) {
    parent::__construct($name, $storage, $event_dispatcher, $typed_config);
    $this->parserManager = $parserManager;
    if (!isset($data)) {
      $data = \Drupal::config($name)->getRawData();
    }
    $this->initWithData($data);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container = NULL, $name = 'markdown.settings', array $data = NULL) {
    if (!$container) {
      $container = \Drupal::getContainer();
    }
    return new static(
      $name,
      $container->get('config.storage'),
      $container->get('event_dispatcher'),
      $container->get('config.typed'),
      $container->get('plugin.manager.markdown.parser'),
      $data
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function load($name, array $data = NULL) {
    if (!isset($data)) {
      $data = \Drupal::config($name)->getRawData();
    }
    return static::create(NULL, $name, $data);
  }

  /**
   * {@inheritdoc}
   */
  public function getParser(array $configuration = []) {
    return $this->parserManager->createInstance($this->getParserId(), $this->getParserConfiguration($configuration));
  }

  /**
   * {@inheritdoc}
   */
  public function getParserId($fallback = TRUE) {
    $parserId = $this->get('parser.id');
    if (!$parserId && $fallback) {
      $parserId = current(array_keys($this->parserManager->installedDefinitions())) ?: $this->parserManager->getFallbackPluginId();
    }
    return $parserId;
  }

  /**
   * {@inheritdoc}
   */
  public function getParserConfiguration(array $configuration = []) {
    return NestedArray::mergeDeep([
      'extensions' => $this->getParserExtensions(),
      'settings' => $this->getParserSettings(),
    ], $configuration);
  }

  /**
   * {@inheritdoc}
   */
  public function getParserExtensions() {
    return $this->get('parser.extensions') ?: [];
  }

  /**
   * {@inheritdoc}
   */
  public function getParserSetting($name, $default = NULL) {
    $value = $this->get("parser.settings.$name");
    return isset($value) ? $value : $default;
  }

  /**
   * {@inheritdoc}
   */
  public function getParserSettings() {
    return $this->get('parser.settings') ?: [];
  }

}
