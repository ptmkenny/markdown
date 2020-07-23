<?php

namespace Drupal\markdown\Routing;

use Drupal\Core\ParamConverter\ParamConverterInterface;
use Drupal\markdown\PluginManager\AllowedHtmlManager;
use Drupal\markdown\PluginManager\ExtensionManagerInterface;
use Drupal\markdown\PluginManager\ParserManagerInterface;
use Symfony\Component\Routing\Route;

class MarkdownParamConverter implements ParamConverterInterface {

  /**
   * The Markdown Allowed HTML Plugin Manager service.
   *
   * @var \Drupal\markdown\PluginManager\AllowedHtmlManager
   */
  protected $allowedHtmlManager;

  /**
   * The Markdown Extension Plugin Manager service.
   *
   * @var \Drupal\markdown\PluginManager\ExtensionManagerInterface
   */
  protected $extensionManager;

  /**
   * The Markdown Parser Plugin Manager service.
   *
   * @var \Drupal\markdown\PluginManager\ParserManagerInterface
   */
  protected $parserManager;


  public function __construct(ParserManagerInterface $parserManager, ExtensionManagerInterface $extensionManager, AllowedHtmlManager $allowedHtmlManager) {
    $this->parserManager = $parserManager;
    $this->extensionManager = $extensionManager;
    $this->allowedHtmlManager = $allowedHtmlManager;
  }

  /**
   * {@inheritdoc}
   */
  public function convert($value, $definition, $name, array $defaults) {
    $type = substr($definition['type'], 9);
    switch ($type) {
      case 'parser':
        return $this->parserManager->createInstance($value);

      case 'extension':
        return $this->extensionManager->createInstance($value);

      case 'allowed_html':
        return $this->allowedHtmlManager->createInstance($value);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function applies($definition, $name, Route $route) {
    return isset($definition['type']) && strpos($definition['type'], 'markdown:') !== FALSE;
  }

}
