<?php

namespace Drupal\markdown\Plugin\Markdown;

use Drupal\Core\Cache\RefinableCacheableDependencyTrait;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Theme\ActiveTheme;
use Drupal\markdown\Render\ParsedMarkdown;
use Drupal\markdown\Traits\SettingsTrait;
use Drupal\markdown\Util\FilterHtml;

/**
 * The parser used as a fallback when the requested one doesn't exist.
 *
 * @MarkdownParser(
 *   id = "_missing_parser",
 *   label = @Translation("Missing Parser"),
 *   installed = false,
 * )
 */
class MissingParser extends InstallablePluginBase implements ParserInterface {

  use RefinableCacheableDependencyTrait;
  use SettingsTrait;

  /**
   * {@inheritdoc}
   */
  public function getAllowedHtml() {
    return $this->config()->get('render_strategy.allowed_html');
  }

  /**
   * {@inheritdoc}
   */
  public function getAllowedHtmlPlugins(ActiveTheme $activeTheme = NULL) {
    return $this->config()->get('render_strategy.plugins') ?: [];
  }

  /**
   * {@inheritdoc}
   */
  protected function getConfigType() {
    return 'markdown_parser';
  }

  /**
   * {@inheritdoc}
   */
  public function getConfiguration() {
    $renderStrategy = $this->getRenderStrategy();
    $configuration['render_strategy'] = ['type' => $renderStrategy];
    if ($renderStrategy === static::FILTER_OUTPUT) {
      $configuration['render_strategy']['allowed_html'] = $this->getAllowedHtml();
      $configuration['render_strategy']['plugins'] = $this->getAllowedHtmlPlugins();
    }
    return $configuration;
  }

  /**
   * {@inheritdoc}
   */
  public function getRenderStrategy() {
    $type = $this->config()->get('render_strategy.type');
    return isset($type) ? $type : static::FILTER_OUTPUT;
  }

  /**
   * {@inheritdoc}
   */
  protected function convertToHtml($markdown, LanguageInterface $language = NULL) {
    return $markdown;
  }

  /**
   * {@inheritdoc}
   */
  public function parse($markdown, LanguageInterface $language = NULL) {
    $html = (string) FilterHtml::fromParser($this)->process($markdown, $language ? $language->getId() : NULL);
    return ParsedMarkdown::create($markdown, $html, $language);
  }

}
