<?php

namespace Drupal\markdown\Plugin\Filter;

use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Entity\EntityFormInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Render\Markup;
use Drupal\Core\Url;
use Drupal\filter\Entity\FilterFormat;
use Drupal\filter\FilterProcessResult;
use Drupal\filter\Plugin\FilterBase;
use Drupal\markdown\Config\ImmutableMarkdownConfig;
use Drupal\markdown\Form\SettingsForm;
use Drupal\markdown\Markdown as MarkdownService;
use Drupal\markdown\PluginManager\ParserManagerInterface;
use Drupal\markdown\Traits\FilterFormatAwareTrait;
use Drupal\markdown\Traits\MoreInfoTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a filter for Markdown.
 *
 * @Filter(
 *   id = "markdown",
 *   title = @Translation("Markdown"),
 *   description = @Translation("Allows content to be submitted using Markdown, a simple plain-text syntax that is filtered into valid HTML."),
 *   type = Drupal\filter\Plugin\FilterInterface::TYPE_MARKUP_LANGUAGE,
 *   weight = -15,
 * )
 */
class FilterMarkdown extends FilterBase implements FilterMarkdownInterface, ContainerFactoryPluginInterface {

  use FilterFormatAwareTrait;
  use MoreInfoTrait;

  /**
   * The Markdown Settings for this filter.
   *
   * @var \Drupal\markdown\Config\MarkdownConfig
   */
  protected $markdownSettings;

  /**
   * The Markdown parser as set by the filter.
   *
   * @var \Drupal\markdown\Plugin\Markdown\ParserInterface
   */
  protected $parser;

  /**
   * The Markdown Parser Plugin Manager service.
   *
   * @var \Drupal\markdown\PluginManager\ParserManagerInterface
   */
  protected $parserManager;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ParserManagerInterface $parserManager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->parserManager = $parserManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('plugin.manager.markdown.parser')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    return $this->getParser()->calculateDependencies();
  }

  /**
   * {@inheritdoc}
   */
  public function getConfiguration() {
    $configuration = parent::getConfiguration();

    // Ensure any filter format set is added to the configuration. This is
    // needed in the event the filters configuration is cached in the database.
    // @see filter_formats()
    // @see markdown_filter_format_load()
    $filterFormat = $this->getFilterFormat();
    $configuration['filterFormat'] = $filterFormat ? $filterFormat->id() : NULL;

    return $configuration;
  }

  /**
   * {@inheritdoc}
   */
  public function getParser() {
    if (!isset($this->parser)) {
      // Filter is using a specific parser/configuration.
      $configuration = $this->markdownSettings()->get('parser') ?: [];
      $configuration['filter'] = $this;
      // Filter using specific parser.
      if (!empty($configuration['id'])) {
        $this->parser = $this->parserManager->createInstance($configuration['id'], $configuration);
      }
      // Filter using global parser.
      else {
        $this->parser = MarkdownService::create()->getParser(NULL, $configuration);
      }
    }
    return $this->parser;
  }

  /**
   * {@inheritdoc}
   */
  public function isEnabled() {
    return !!$this->status;
  }

  /**
   * Retrieves the Markdown Settings object.
   *
   * @return \Drupal\markdown\Config\ImmutableMarkdownConfig
   */
  protected function markdownSettings(array $settings = NULL) {
    if (!$this->markdownSettings) {
      $this->markdownSettings = ImmutableMarkdownConfig::load("filter_settings.{$this->pluginId}", isset($settings) ? $settings : $this->settings)->setKeyPrefix('parser');
    }
    return $this->markdownSettings;
  }

  /**
   * {@inheritdoc}
   */
  public function process($text, $langcode = NULL) {
    // Only use the parser to process the text if it's not empty.
    if (!empty($text)) {
      $language = $langcode ? \Drupal::languageManager()->getLanguage($langcode) : NULL;
      $text = $this->getParser()->parse($text, $language);
    }
    return new FilterProcessResult($text);
  }

  /**
   * {@inheritdoc}
   */
  public function setConfiguration(array $configuration) {
    // Normalize any passed filter format. This is needed in the event the
    // filter is being loaded from cached database configuration.
    // @see \Drupal\markdown\Plugin\Filter\Markdown::getConfiguration()
    // @see filter_formats()
    // @see markdown_filter_format_load()
    if (isset($configuration['filterFormat'])) {
      // Filter format is an entity, ensure configuration has an identifier.
      if ($configuration['filterFormat'] instanceof FilterFormat) {
        $this->setFilterFormat($configuration['filterFormat']);
        $configuration['filterFormat'] = $configuration['filterFormat']->id();
      }
      // Filter format is an identifier, ensure that it is properly loaded.
      elseif (is_string($configuration['filterFormat']) && (!$this->filterFormat || $this->filterFormat->id() !== $configuration['filterFormat'])) {
        if ($currentFilterFormat = drupal_static('markdown_filter_format_load')) {
          $filterFormat = $currentFilterFormat;
        }
        else {
          /** @var \Drupal\filter\Entity\FilterFormat $filterFormat */
          $filterFormat = FilterFormat::load($configuration['filterFormat']);
        }
        $this->setFilterFormat($filterFormat);
      }
    }

    // Remove any vertical tabs value populated by form submission.
    unset($configuration['settings']['vertical_tabs']);

    // Sanitize parser values.
    if (!empty($configuration['settings']['parser'])) {
      $settings = $this->markdownSettings($configuration['settings']);
      $markdownSettingsForm = SettingsForm::create(NULL, $settings);
      $config = $markdownSettingsForm->getConfigFromValues($settings->getName(), $configuration['settings']);
      $configuration['settings'] = array_merge($configuration['settings'], $config->get());
    }

    // Remove dependencies, this is added above.
    // @see \Drupal\markdown\Plugin\Filter\Markdown::calculateDependencies()
    unset($configuration['settings']['dependencies']);

    // Reset the markdown settings so it can be reconstructed.
    $this->markdownSettings = NULL;

    return parent::setConfiguration($configuration);
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $element, FormStateInterface $form_state) {
    // Disable form cache as MarkdownSettingsForm uses sub-forms and AJAX and
    // attempting to cache it causes a fatal due to the database getting
    // serialized somewhere.
    // @todo figure out what's going on here?
    $form_state->disableCache();

    // If there's no filter format set, attempt to extract it from the form.
    if (!$this->filterFormat && ($formObject = $form_state->getFormObject()) && $formObject instanceof EntityFormInterface && ($entity = $formObject->getEntity()) && $entity instanceof FilterFormat) {
      $this->setFilterFormat($entity);
    }

    $markdownSettingsForm = SettingsForm::create(NULL, $this->markdownSettings());
    $markdownSettingsForm->setFilter($this);
    return $markdownSettingsForm->buildSubform($element, $form_state);
  }

  /**
   * The process callback for "text_format" elements.
   *
   * @param array $element
   *   The render array element being processed, passed by reference.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current form state.
   * @param array $complete_form
   *   The complete form, passed by reference.
   *
   * @return array
   *   The modified $element.
   */
  public static function processTextFormat(array &$element, FormStateInterface $form_state, array &$complete_form) {
    // Immediately return if not a valid filter format.
    if (!isset($element['#format']) || !($formats = filter_formats()) || !isset($formats[$element['#format']])) {
      return $element;
    }

    /** @var \Drupal\filter\FilterFormatInterface $format */
    $format = $formats[$element['#format']];
    try {
      if (($markdown = $format->filters('markdown')) && $markdown->status) {
        $element['format']['help']['about'] = [
          '#type' => 'link',
          // Shamelessly copied from GitHub's Octicon icon set.
          // @todo Revisit this?
          // @see https://primer.style/octicons/markdown
          '#title' => Markup::create('<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" width="16" height="16"><path fill-rule="evenodd" d="M14.85 3H1.15C.52 3 0 3.52 0 4.15v7.69C0 12.48.52 13 1.15 13h13.69c.64 0 1.15-.52 1.15-1.15v-7.7C16 3.52 15.48 3 14.85 3zM9 11H7V8L5.5 9.92 4 8v3H2V5h2l1.5 2L7 5h2v6zm2.99.5L9.5 8H11V5h2v3h1.5l-2.51 3.5z"></path></svg>'),
          '#url' => Url::fromRoute('filter.tips_all')->setOptions([
            'attributes' => [
              'class' => ['markdown'],
              'target' => '_blank',
              'title' => t('Styling with Markdown is supported'),
            ],
          ]),
        ];
      }
    }
    /* @noinspection PhpRedundantCatchClauseInspection */
    catch (PluginNotFoundException $exception) {
      // Intentionally do nothing.
    }
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function tips($long = FALSE) {
    // On the "short" tips, don't show anything.
    // @see \Drupal\markdown\Plugin\Filter\FilterMarkdown::processTextFormat
    if (!$long) {
      return NULL;
    }
    return $this->moreInfo($this->t('Parses markdown and converts it to HTML.'), 'https://www.drupal.org/docs/8/modules/markdown');
  }

}
