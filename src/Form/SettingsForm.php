<?php

namespace Drupal\markdown\Form;

use Drupal\Core\Cache\CacheTagsInvalidatorInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\TypedConfigManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\ElementInfoManagerInterface;
use Drupal\markdown\Config\MarkdownConfig;
use Drupal\markdown\MarkdownInterface;
use Drupal\markdown\Plugin\Filter\FilterMarkdown;
use Drupal\markdown\PluginManager\ParserManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Markdown Settings Form.
 *
 * @deprecated in markdown:8.x-2.0 and is removed from markdown:3.0.0.
 *   Use \Drupal\markdown\Form\ParserConfigurationForm instead.
 * @see https://www.drupal.org/project/markdown/issues/3142418
 */
class SettingsForm extends ParserConfigurationForm {

  /**
   * The Markdown service.
   *
   * @var \Drupal\markdown\MarkdownInterface
   */
  protected $markdown;

  /**
   * MarkdownSettingsForm constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The Config Factory service.
   * @param \Drupal\Core\Config\TypedConfigManagerInterface $typedConfigManager
   *   The Typed Config Manager service.
   * @param \Drupal\Core\Cache\CacheTagsInvalidatorInterface $cacheTagsInvalidator
   *   The Cache Tags Invalidator service.
   * @param \Drupal\Core\Render\ElementInfoManagerInterface $elementInfo
   *   The Element Info Plugin Manager service.
   * @param \Drupal\markdown\MarkdownInterface $markdown
   *   The Markdown service.
   * @param \Drupal\markdown\PluginManager\ParserManagerInterface $parserManager
   *   The Markdown Parser Plugin Manager service.
   * @param \Drupal\markdown\Config\MarkdownConfig $settings
   *   The markdown settings config.
   */
  public function __construct(ConfigFactoryInterface $configFactory, TypedConfigManagerInterface $typedConfigManager, CacheTagsInvalidatorInterface $cacheTagsInvalidator, ElementInfoManagerInterface $elementInfo, MarkdownInterface $markdown, ParserManagerInterface $parserManager, MarkdownConfig $settings) {
    parent::__construct($configFactory, $typedConfigManager, $cacheTagsInvalidator, $elementInfo, $parserManager);
    $this->markdown = $markdown;
    $this->settings = $settings;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container = NULL, MarkdownConfig $settings = NULL) {
    if (!$container) {
      $container = \Drupal::getContainer();
    }
    return new static(
      $container->get('config.factory'),
      $container->get('config.typed'),
      $container->get('cache_tags.invalidator'),
      $container->get('plugin.manager.element_info'),
      $container->get('markdown'),
      $container->get('plugin.manager.markdown.parser'),
      $settings ?: MarkdownConfig::load('markdown.settings', NULL, $container)->setKeyPrefix('parser')
    );
  }

  /**
   * Indicates whether user is currently viewing the site-wide settings form.
   *
   * @return bool
   *   TRUE or FALSE
   *
   * @deprecated in markdown:8.x-2.0 and is removed from markdown:3.0.0.
   *   No replacement. Check route name yourself.
   * @see https://www.drupal.org/project/markdown/issues/3142418
   */
  public static function siteWideSettingsForm() {
    return \Drupal::routeMatch()->getRouteName() === 'markdown.settings';
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'markdown_configuration';
  }

  /**
   * Builds the subform for markdown settings.
   *
   * Note: building a subform requires that it's ultimately constructed in
   * a #process callback. This is to ensure the complete form (from the parent)
   * has been constructed properly.
   *
   * @param array $element
   *   A render array element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array
   *   The modified render array element.
   *
   * @deprecated in markdown:8.x-2.0 and is removed from markdown:3.0.0.
   *   No replacement.
   * @see https://www.drupal.org/project/markdown/issues/3142418
   */
  public function buildSubform(array $element, FormStateInterface $form_state) {
    return $element;
  }

  /**
   * The AJAX callback used to return the parser ajax wrapper.
   *
   * @deprecated in markdown:8.x-2.0 and is removed from markdown:3.0.0. Use
   *   \Drupal\markdown\Plugin\Filter\FilterMarkdown::ajaxChangeParser instead.
   * @see https://www.drupal.org/project/markdown/issues/3142418
   */
  public static function ajaxChangeParser(array $form, FormStateInterface $form_state) {
    return FilterMarkdown::ajaxChangeParser($form, $form_state);
  }

  /**
   * Retrieves configuration from values.
   *
   * @param array $values
   *   An array of values.
   *
   * @return array
   *   The configuration array.
   *
   * @deprecated in markdown:8.x-2.0 and is removed from markdown:3.0.0.
   *   Use \Drupal\markdown\Form\ParserConfigurationForm::getConfigFromValues
   *   instead.
   * @see https://www.drupal.org/project/markdown/issues/3142418
   */
  public function getConfigurationFromValues(array $values) {
    $config = $this->getConfigFromValues($this->settings->getName(), $values);
    return $config->get();
  }

}
