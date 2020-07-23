<?php

namespace Drupal\markdown\Form;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Cache\CacheTagsInvalidatorInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\TypedConfigManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\PluginDependencyTrait;
use Drupal\Core\Plugin\PluginFormInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\Render\ElementInfoManagerInterface;
use Drupal\Core\Render\Markup;
use Drupal\Core\Url;
use Drupal\markdown\Config\MarkdownConfig;
use Drupal\markdown\Markdown;
use Drupal\markdown\MarkdownInterface;
use Drupal\markdown\Plugin\Markdown\ExtensibleParserInterface;
use Drupal\markdown\Plugin\Markdown\ParserInterface;
use Drupal\markdown\Plugin\Markdown\RenderStrategyInterface;
use Drupal\markdown\Plugin\Markdown\SettingsInterface;
use Drupal\markdown\PluginManager\AllowedHtmlManager;
use Drupal\markdown\PluginManager\ParserManagerInterface;
use Drupal\markdown\Traits\FilterAwareTrait;
use Drupal\markdown\Traits\FormTrait;
use Drupal\markdown\Traits\MoreInfoTrait;
use Drupal\markdown\Util\FilterAwareInterface;
use Drupal\markdown\Util\FilterFormatAwareInterface;
use Drupal\markdown\Util\FilterHtml;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Markdown Settings Form.
 */
class SettingsForm extends FormBase implements FilterAwareInterface {

  use FilterAwareTrait;
  use FormTrait;
  use MoreInfoTrait;
  use PluginDependencyTrait;

  /**
   * The Cache Tags Invalidator service.
   *
   * @var \Drupal\Core\Cache\CacheTagsInvalidatorInterface
   */
  protected $cacheTagsInvalidator;

  /**
   * The Element Info Plugin Manager service.
   *
   * @var \Drupal\Core\Render\ElementInfoManagerInterface
   */
  protected $elementInfo;

  /**
   * The Markdown service.
   *
   * @var \Drupal\markdown\MarkdownInterface
   */
  protected $markdown;

  /**
   * The Markdown Parser Plugin Manager service.
   *
   * @var \Drupal\markdown\PluginManager\ParserManagerInterface
   */
  protected $parserManager;

  /**
   * The Markdown Settings.
   *
   * @var \Drupal\markdown\Config\MarkdownConfig
   */
  protected $settings;

  /***
   * The typed config manager.
   *
   * @var \Drupal\Core\Config\TypedConfigManagerInterface
   */
  protected $typedConfigManager;

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
    $this->configFactory = $configFactory;
    $this->cacheTagsInvalidator = $cacheTagsInvalidator;
    $this->elementInfo = $elementInfo;
    $this->markdown = $markdown;
    $this->parserManager = $parserManager;
    $this->settings = $settings;
    $this->typedConfigManager = $typedConfigManager;
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
   *   No direct replacement. Check route name yourself or if there is a parser
   *   object set at $form_state->get('markdownParser').
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
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, ParserInterface $parser = NULL) {
    // Immediately redirect to the overview form if no parser was provided.
    if (!$parser) {
      return $this->redirect('markdown.overview');
    }
    // Otherwise, if there is a parser, ensure it's a valid one.
    elseif ($parser->getPluginId() === $this->parserManager->getFallbackPluginId()) {
      throw new NotFoundHttpException();
    }

    $form_state->set('markdownParser', $parser);

    $form += [
      '#parents' => [],
      '#title' => $this->t('Edit @parser', [
        '@parser' => $parser->getLabel(FALSE),
      ]),
    ];

    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save configuration'),
      '#button_type' => 'primary',
    ];

    // By default, render the form using system-config-form.html.twig.
    $form['#theme'] = 'system_config_form';

    // Build subform.
    $form['subform'] = $this->buildSubform(['#parents' => []], $form_state);

    return $form;
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
   */
  public function buildSubform(array $element, FormStateInterface $form_state) {
    if (!isset($element['#type'])) {
      $element['#type'] = 'container';
    }
    $process = $this->elementInfo->getInfoProperty($element['#type'], '#process', []);
    $process[] = [$this, 'processSubform'];
    $element['#process'] = $process;
    return $element;
  }

  /**
   * Process callback for constructing markdown settings for this filter.
   *
   * @param array $element
   *   The element being processed.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current form state.
   * @param array $complete_form
   *   The complete form, passed by reference.
   *
   * @return array
   *   The processed element.
   */
  public function processSubform(array $element, FormStateInterface $form_state, array &$complete_form) {
    $siteWideParser = $form_state->get('markdownParser');

    // Immediately return if there are no installed parsers.
    if (!$this->parserManager->getDefinitions(FALSE)) {
      $element['missing'] = [
        '#type' => 'item',
        '#title' => $this->t('No markdown parsers installed.'),
      ];

      if (($markdownOverview = Url::fromRoute('markdown.overview')) && $markdownOverview->access()) {
        $element['missing']['#description'] = $this->t('Visit the <a href=":markdown.overview" target="_blank">Markdown Overview</a> page for more details.', [
          ':markdown.overview' => $markdownOverview->toString(),
        ]);
      }
      else {
        $element['missing']['#description'] = $this->t('Ask your site administrator to install a <a href=":supported_parsers" target="_blank">supported markdown parser</a>.', [
          ':supported_parsers' => Markdown::DOCUMENTATION_URL . '/parsers',
        ]);
      }

      // Hide the actions on the site-wide settings form.
      if ($siteWideParser) {
        $complete_form['actions']['#access'] = FALSE;
      }

      return $element;
    }

    // Add #validate and #submit handlers. These help validate and submit
    // the various markdown plugin forms for parsers and extensions.
    if ($validationHandlers = $form_state->getValidateHandlers()) {
      if (!in_array([$this, 'validateSubform'], $validationHandlers)) {
        array_unshift($validationHandlers, [$this, 'validateSubform']);
        $form_state->setValidateHandlers($validationHandlers);
      }
    }
    else {
      $complete_form['#validate'][] = [$this, 'validateSubform'];
    }

    // Keep track of subform parents for the validation and submit handlers.
    $form_state->set('markdownSubformParents', ($parents = isset($element['#parents']) ? $element['#parents'] : []));
    $form_state->set('markdownSubformArrayParents', $element['#array_parents']);

    // Add the markdown/admin library to update summaries in vertical tabs.
    $complete_form['#attached']['library'][] = 'markdown/admin';

    // Build a wrapper for the ajax response.
    $form_state->set('markdownAjaxId', ($markdownAjaxId = Html::getUniqueId('markdown-parser-ajax')));
    $element['ajax'] = static::createElement([
      '#type' => 'container',
      '#id' => $markdownAjaxId,
      '#attributes' => [
        'data' => [
          'markdownElement' => 'wrapper',
        ],
      ],
    ]);

    // Build vertical tabs that parser and extensions will go into.
    $element['ajax']['vertical_tabs'] = [
      '#type' => 'vertical_tabs',
      '#parents' => array_merge($parents, ['vertical_tabs']),
    ];

    // Determine the group that details should be referencing for vertical tabs.
    $form_state->set('markdownGroup', ($group = implode('][', array_merge($parents, ['vertical_tabs']))));

    // Create a subform state.
    $subform_state = SubformState::createForSubform($element, $complete_form, $form_state);

    // Build the parser form.
    return $this->buildParser($element, $subform_state);
  }

  /**
   * Builds the parser form elements.
   *
   * @param array $element
   *   An element in a render array.
   * @param \Drupal\markdown\Form\SubformStateInterface $form_state
   *   The form state.
   *
   * @return array
   *   The $element passed, modified to include the parser element.
   */
  protected function buildParser(array $element, SubformStateInterface $form_state) {
    /* @var \Drupal\markdown\Plugin\Markdown\ParserInterface $siteWideParser */
    $siteWideParser = $form_state->get('markdownParser');

    // If the triggering element is the parser select element, clear out any
    // parser values other than the identifier. This is necessary since the
    // parser has switched and the previous parser settings may not translate
    // correctly to the new parser.
    if (!$siteWideParser && ($trigger = $form_state->getTriggeringElement()) && isset($trigger['#ajax']['callback']) && $trigger['#ajax']['callback'] === '\Drupal\markdown\Form\SettingsForm::ajaxChangeParser' && ($parserId = $form_state->getValue(['parser', 'id']))) {
      $parents = $form_state->createParents();
      $input = &NestedArray::getValue($form_state->getUserInput(), $parents);
      $values = &NestedArray::getValue($form_state->getValues(), $parents);
      $input['parser'] = ['id' => $parserId];
      $values['parser'] = ['id' => $parserId];
    }

    $markdownAjaxId = $form_state->get('markdownAjaxId');
    $markdownGroup = $form_state->get('markdownGroup');
    $labels = $this->parserManager->labels();

    $parents = isset($element['#parents']) ? $element['#parents'] : [];

    $element['parser'] = [
      '#weight' => -20,
      '#type' => 'details',
      '#title' => $this->t('Parser'),
      '#tree' => TRUE,
      '#parents' => array_merge($parents, ['parser']),
      '#group' => $markdownGroup,
    ];
    $parserElement = &$element['parser'];
    $parserSubform = SubformState::createForSubform($parserElement, $element, $form_state);

    // Because the "id" element isn't yet created (below), the new
    // $parserSubform state cannot be used here.
    if ($siteWideParser) {
      $parserId = $siteWideParser->getPluginId();
    }
    else {
      $parserId = $form_state->getValue(['parser', 'id'], $this->settings->get('parser.id'));
    }

    $sitePrefix = 'site:';
    $parserIdHasSitePrefix = strpos($parserId, $sitePrefix) === 0;
    $realParserId = $parserIdHasSitePrefix ? substr($parserId, 5) : $parserId;

    // Include site-wide parser options if not on the site-wide settings page.
    if (!$siteWideParser) {
      $standaloneParsers = $labels;
      $labels = [];

      // Check if parser exists and, if not, prepend an option showing it missing.
      if (!$this->parserManager->hasDefinition($realParserId)) {
        $labels[(string) $this->t('Missing Parser')] = [$realParserId => $realParserId];
      }

      // Add each installed site-wide parser.
      $siteWideParsers = [];
      foreach ($this->parserManager->installed() as $name => $installedParser) {
        $siteWideParsers["$sitePrefix$name"] = $installedParser->getLabel(TRUE);
      }
      $labels[(string) $this->t('Site-wide Parsers')] = $siteWideParsers;

      // Restore the original options as the standalone parsers.
      $labels[(string) $this->t('Standalone Parser')] = $standaloneParsers;
    }

    if ($siteWideParser) {
      $parserElement['id'] = [
        '#type' => 'hidden',
        '#default_value' => $parserId,
      ];
    }
    else {
      $parserElement['id'] = static::createElement([
        '#type' => 'select',
        '#options' => $labels,
        '#default_value' => $parserId,
        '#attributes' => [
          'data' => [
            'markdownSummary' => 'parser',
            'markdownId' => $parserId,
          ],
        ],
        '#ajax' => [
          'callback' => '\Drupal\markdown\Form\SettingsForm::ajaxChangeParser',
          'event' => 'change',
          'wrapper' => $markdownAjaxId,
        ],
      ]);
    }



    $configuration = NestedArray::mergeDeep($this->settings->get('parser') ?: [], $form_state->getValue('parser', []));
    $config = $this->config("markdown.parser.$realParserId")->initWithData($configuration);

    // If the parser identifier is prefixed with "site:", then it should
    // be using the site-wide parser configuration.
    if (!$siteWideParser && $parserIdHasSitePrefix) {
      // Load site-wide parser, ensuring that the filter's render strategy
      // settings override the site-wide parser's.
      $parser = $this->markdown->getParser($realParserId, [
        'render_strategy' => $this->settings->get('parser.render_strategy'),
      ]);
    }
    // Retrieve the parser.
    else {
      $parser = $siteWideParser ?: $this->parserManager->createInstance($realParserId, $configuration);
    }

    // Allow the parser to be filter aware.
    if ($parser instanceof FilterAwareInterface && ($filter = $this->getFilter())) {
      $parser->setFilter($filter);
    }

    // Indicate whether parser is installed.
    $this->addDataAttribute($parserElement['id'], 'markdownInstalled', $parser->isInstalled());

    // Add the parser description.
    $parserElement['id']['#description'] = $parser->getDescription();
    if ($url = $parser->getUrl()) {
      $parserElement['id']['#description'] = $this->moreInfo($parserElement['id']['#description'], $url);
    }

    if ($parserIdHasSitePrefix) {
      if (($markdownParserEditUrl = Url::fromRoute('markdown.parser.edit', ['parser' => $parser])) && $markdownParserEditUrl->access()) {
        $parserElement['id']['#description'] = $this->t('Site-wide markdown settings can be adjusted by visiting the site-wide <a href=":markdown.parser.edit" target="_blank">@label</a> parser.', [
          '@label' => $parser->getLabel(FALSE),
          ':markdown.parser.edit' => $markdownParserEditUrl->toString(),
        ]);
      }
      else {
        $parserElement['id']['#description'] = $this->t('Site-wide markdown settings can only be adjusted by administrators.');
      }
    }

    $build = $parser->buildStatus();
    $parserElement['id']['#field_suffix'] = \Drupal::service('renderer')->renderPlain($build);

    // Build render strategy.
    $parserElement = $this->buildRenderStrategy($parser, $parserElement, $parserSubform);

    // Only build parser settings and extensions if it's not set to site-wide.
    if (!$parserIdHasSitePrefix) {
      // Build parser settings.
      $parserElement = $this->buildParserSettings($parser, $parserElement, $parserSubform);

      // Build parser extensions.
      $parserElement = $this->buildParserExtensions($parser, $parserElement, $parserSubform);
    }

    // Only show the parser if there are settings and/or extensions.
    $children = array_diff(Element::getVisibleChildren($parserElement), ['render_strategy']);
    if (!$children) {
      $parserElement['#type'] = 'container';
      unset($parserElement['#group']);
    }
    // Rename "Parser" vertical tab and remove details wrapper from settings.
    elseif ($siteWideParser && in_array('settings', $children)) {
      $parserElement['#title'] = $this->t('Settings');
      $parserElement['settings']['#type'] = 'container';
    }

    return $element;
  }

  /**
   * Builds the settings for a specific parser.
   *
   * @param \Drupal\markdown\Plugin\Markdown\ParserInterface $parser
   *   The parser.
   * @param array $element
   *   An element in a render array.
   * @param \Drupal\markdown\Form\SubformStateInterface $form_state
   *   The form state.
   *
   * @return array
   *   The $element passed, modified to include the parser settings element.
   */
  protected function buildParserSettings(ParserInterface $parser, array $element, SubformStateInterface $form_state) {
    // If parser doesn't implement configuration forms, do nothing.
    if (!($parser instanceof PluginFormInterface)) {
      return $element;
    }

    $element['settings'] = [
      '#type' => 'details',
      '#title' => $this->t('Settings'),
      '#open' => TRUE,
    ];
    $parserSettingsSubform = SubformState::createForSubform($element['settings'], $element, $form_state);
    $element['settings'] = $parser->buildConfigurationForm($element['settings'], $parserSettingsSubform);

    // Only show settings if there are settings.
    $element['settings']['#access'] = !!Element::getVisibleChildren($element['settings']);

    return $element;

  }

  /**
   * Builds the extension settings for a specific parser.
   *
   * @param \Drupal\markdown\Plugin\Markdown\ParserInterface $parser
   *   The parser.
   * @param array $element
   *   An element in a render array.
   * @param \Drupal\markdown\Form\SubformStateInterface $form_state
   *   The form state.
   *
   * @return array
   *   The $element passed, modified to include the parser extension elements.
   */
  protected function buildParserExtensions(ParserInterface $parser, array $element, SubformStateInterface $form_state) {
    // Immediately return if parser isn't extensible.
    if (!($parser instanceof ExtensibleParserInterface)) {
      return $element;
    }

    $markdownGroup = $form_state->get('markdownGroup');

    $extensions = $parser->extensions();
    if (!$extensions) {
      return $element;
    }

    $parents = $element['#parents'];

    $element['extensions'] = ['#type' => 'container'];

    // Add any specific extension settings.
    foreach ($extensions as $extensionId => $extension) {
      $label = $extension->getLabel(FALSE);
      $element['extensions'][$extensionId] = [
        '#type' => 'details',
        '#title' => $label,
        '#description' => $extension->getDescription(),
        '#description_display' => 'before',
        '#group' => $markdownGroup,
        '#parents' => array_merge($parents, ['extensions', $extensionId]),
      ];

      $extensionElement = &$element['extensions'][$extensionId];
      $extensionSubform = SubformState::createForSubform($element['extensions'][$extensionId], $element, $form_state);

      $bundled = in_array($extensionId, $parser->getBundledExtensionIds(), TRUE);
      $installed = $extension->isInstalled();
      $enabled = $extensionSubform->getValue('enabled', $extension->isEnabled());
      $url = $extension->getUrl();

      if ($installed) {
        $messages = [];
        if (!$extension->isPreferredLibraryInstalled()) {
          if ($preferredLibrary = $extension->getPreferredLibrary()) {
            $messages['status'][] = $this->t('Upgrade available: <a href=":url" target="_blank">:url</a>', [
              ':url' => $preferredLibrary->url ?: $url->toString(),
            ]);

          }
        }
        if ($deprecation = $extension->getDeprecated()) {
          $messages['warning'][] = $this->t('The currently installed extension (<a href=":url" target="_blank">:url</a>) is no longer supported. @deprecation', [
            ':url' => $url->toString(),
            '@deprecation' => $deprecation,
          ]);
        }
        if ($experimental = $extension->getExperimental()) {
          $message = $this->t('The currently installed extension (<a href=":url" target="_blank">:url</a>) is considered experimental; its functionality cannot be guaranteed.', [
            ':url' => $url->toString(),
          ]);
          if ($experimental !== TRUE) {
            $message = new FormattableMarkup('@message @experimental', [
              '@message' => $message,
              '@experimental' => $experimental,
            ]);
          }
          $messages['status'][] = $message;
        }
        if ($messages) {
          $extensionElement['message'] = [
            '#weight' => -10,
            '#theme' => 'status_messages',
            '#message_list' => $messages,
            '#status_headings' => [
              'error' => $this->t('Error message'),
              'info' => $this->t('Info message'),
              'status' => $this->t('Status message'),
              'warning' => $this->t('Warning message'),
            ],
          ];
        }
      }

      // Extension enabled checkbox.
      $extensionElement['enabled'] = static::createElement([
        '#type' => 'checkbox',
        '#title' => $this->t('Enable'),
        '#attributes' => [
          'data' => [
            'markdownElement' => 'extension',
            'markdownSummary' => 'extension',
            'markdownId' => $extensionId,
            'markdownLabel' => $label,
            'markdownInstalled' => $installed,
            'markdownBundle' => $bundled ? $parser->getLabel(FALSE) : FALSE,
            'markdownRequires' => $extension->requires(),
            'markdownRequiredBy' => $extension->requiredBy(),
          ],
        ],
        '#default_value' => $bundled || $enabled,
        '#disabled' => $bundled || !$installed,
      ]);

      if (!$installed) {
        $extensionElement['installation_instructions'] = [
          '#type' => 'fieldset',
          '#title' => $this->t('Installation Instructions'),
        ];
        if (!$extension->hasMultipleLibraries() && $url) {
          $extensionElement['installation_instructions']['#description'] = $this->moreInfo(isset($extensionElement['enabled']['#description']) ? $extensionElement['enabled']['#description'] : NULL, $url);
        }
        if ($instructions = $extension->buildSupportedLibraries()) {
          $extensionElement['installation_instructions'] = $instructions;
        }
      }
      elseif ($url) {
        $extensionElement['enabled']['#description'] = $this->moreInfo(isset($extensionElement['enabled']['#description']) ? $extensionElement['enabled']['#description'] : NULL, $url);
      }

      // Installed extension settings.
      if ($installed && $extension instanceof PluginFormInterface) {
        $extensionElement['settings'] = [
          '#type' => 'details',
          '#title' => $this->t('Settings'),
          '#open' => TRUE,
        ];
        $extensionSettingsElement = &$extensionElement['settings'];
        $extensionSettingsSubform = SubformState::createForSubform($extensionSettingsElement, $extensionElement, $extensionSubform);
        $extensionSubform->addElementState($extensionSettingsElement, 'visible', 'enabled', ['checked' => TRUE]);

        $extensionSettingsElement = $extension->buildConfigurationForm($extensionSettingsElement, $extensionSettingsSubform);
        $extensionSettingsElement['#access'] = !!Element::getVisibleChildren($extensionSettingsElement);
      }
    }

    // Only show extensions if there are extensions.
    $element['extensions']['#access'] = !!Element::getVisibleChildren($element['extensions']);

    return $element;
  }

  /**
   * Builds the render strategy for a specific parser.
   *
   * @param \Drupal\markdown\Plugin\Markdown\ParserInterface $parser
   *   The parser.
   * @param array $element
   *   An element in a render array.
   * @param \Drupal\markdown\Form\SubformStateInterface $form_state
   *   The form state.
   * @param bool $siteWide
   *   Flag indicating whether the parser is the site-wide parser.
   *
   * @return array
   *   The $element passed, modified to include the render strategy elements.
   */
  protected function buildRenderStrategy(ParserInterface $parser, array $element, SubformStateInterface $form_state, $siteWide = FALSE) {
    $element['render_strategy'] = [
      '#weight' => -10,
      '#type' => 'details',
      '#title' => $this->t('Render Strategy'),
      '#group' => $form_state->get('markdownGroup'),
    ];
    $renderStrategySubform = &$element['render_strategy'];
    $renderStrategySubformState = SubformState::createForSubform($renderStrategySubform, $element, $form_state);

    $renderStrategySubform['type'] = [
      '#weight' => -10,
      '#type' => 'select',
      '#description' => $this->t('Determines the strategy to use when dealing with user provided HTML markup.'),
      '#default_value' => $renderStrategySubformState->getValue('type', $parser->getRenderStrategy()),
      '#attributes' => [
        'data-markdown-element' => 'render_strategy',
        'data-markdown-summary' => 'render_strategy',
      ],
      '#options' => [
        RenderStrategyInterface::FILTER_OUTPUT => $this->t('Filter Output'),
        RenderStrategyInterface::ESCAPE_INPUT => $this->t('Escape Input'),
        RenderStrategyInterface::STRIP_INPUT => $this->t('Strip Input'),
        RenderStrategyInterface::NONE => $this->t('None'),
      ],
    ];
    $renderStrategySubform['type']['#description'] = $this->moreInfo($renderStrategySubform['type']['#description'], RenderStrategyInterface::DOCUMENTATION_URL . '#xss');

    // Build allowed HTML plugins.
    $renderStrategySubform['plugins'] = [
      '#weight' => -10,
      '#type' => 'item',
      '#input' => FALSE,
      '#title' => $this->t('Allowed HTML'),
      '#description_display' => 'before',
      '#description' => $this->t('The following are registered <code>@MarkdownAllowedHtml</code> plugins that allow HTML tags and attributes based on configuration. These are typically provided by the parser itself, any of its enabled extensions that convert additional HTML tag and potentially various Drupal filters, modules or themes (if supported).'),
    ];
    $renderStrategySubform['plugins']['#description'] = $this->moreInfo($renderStrategySubform['plugins']['#description'], RenderStrategyInterface::DOCUMENTATION_URL);
    $renderStrategySubformState->addElementState($renderStrategySubform['plugins'], 'visible', 'type', ['value' => RenderStrategyInterface::FILTER_OUTPUT]);

    $allowedHtmlManager = AllowedHtmlManager::create();
    foreach ($allowedHtmlManager->appliesTo($parser) as $plugin_id => $allowedHtml) {
      $pluginDefinition = $allowedHtml->getPluginDefinition();
      $label = isset($pluginDefinition['label']) ? $pluginDefinition['label'] : $plugin_id;
      $description = isset($pluginDefinition['description']) ? $pluginDefinition['description'] : '';
      $type = isset($pluginDefinition['type']) ? $pluginDefinition['type'] : 'other';
      if (!isset($renderStrategySubform['plugins'][$type])) {
        $renderStrategySubform['plugins'][$type] = [
          '#type' => 'details',
          '#open' => TRUE,
          '#title' => $this->t(ucfirst($type) . 's'), //phpcs:ignore
          '#parents' => $renderStrategySubformState->createParents(['plugins']),
        ];
        if ($type === 'module') {
          $renderStrategySubform['plugins'][$type]['#weight'] = -10;
        }
        if ($type === 'filter') {
          $renderStrategySubform['plugins'][$type]['#weight'] = -9;
          $renderStrategySubform['plugins'][$type]['#description'] = $this->t('NOTE: these will only be applied when the filter it represents is actually enabled.');
          $renderStrategySubform['plugins'][$type]['#description_display'] = 'before';
        }
        if ($type === 'parser') {
          $renderStrategySubform['plugins'][$type]['#weight'] = -8;
        }
        if ($type === 'extension') {
          $renderStrategySubform['plugins'][$type]['#weight'] = -7;
          $renderStrategySubform['plugins'][$type]['#title'] = $this->t('Extensions');
          $renderStrategySubform['plugins'][$type]['#description'] = $this->t('NOTE: these will only be applied when the parser extension it represents is actually enabled.');
          $renderStrategySubform['plugins'][$type]['#description_display'] = 'before';
        }
        if ($type === 'theme') {
          $renderStrategySubform['plugins'][$type]['#weight'] = -6;
          $renderStrategySubform['plugins'][$type]['#description'] = $this->t('NOTE: these will only be applied when the theme that provides the plugin is the active theme or is a descendant of the active theme.');
          $renderStrategySubform['plugins'][$type]['#description_display'] = 'before';
        }
      }
      $allowedHtmlTags = $allowedHtml->allowedHtmlTags($parser);
      $allowedHtmlPlugins = $parser->getAllowedHtmlPlugins();

      // Determine the default value.
      $defaultValue = NULL;
      if ($allowedHtmlTags) {
        // Setting value.
        if (!isset($defaultValue) && isset($allowedHtmlPlugins[$plugin_id])) {
          $defaultValue = $allowedHtmlPlugins[$plugin_id];
        }
        if (!isset($defaultValue)) {
          if ($type === 'filter' && ($filter = $this->getFilter()) && $filter instanceof FilterFormatAwareInterface && ($format = $filter->getFilterFormat())) {
            $definition = $allowedHtml->getPluginDefinition();
            $filterId = isset($definition['requiresFilter']) ? $definition['requiresFilter'] : $plugin_id;
            $defaultValue = $format->filters()->has($filterId) ? !!$format->filters($filterId)->status : FALSE;
          }
          elseif ($type === 'extension' && $parser instanceof ExtensibleParserInterface && ($parser->extensions()->has($plugin_id))) {
            $defaultValue = $parser->extension($plugin_id)->isEnabled();
          }
          else {
            $defaultValue = TRUE;
          }
        }
      }

      $renderStrategySubform['plugins'][$type][$plugin_id] = [
        '#type' => 'checkbox',
        '#title' => $label,
        '#disabled' => !$allowedHtmlTags,
        '#description' => Markup::create(sprintf('%s<pre><code>%s</code></pre>', $description, $allowedHtmlTags ? htmlentities(FilterHtml::tagsToString($allowedHtmlTags)) : $this->t('No HTML tags provided.'))),
        '#default_value' => $renderStrategySubformState->getValue(['plugins', $plugin_id], $defaultValue),
        '#attributes' => [
          'data-markdown-default-value' => $renderStrategySubformState->getValue(['plugins', $plugin_id], $defaultValue) ? 'true' : 'false',
        ],
      ];
      if ($plugin_id === 'markdown') {
        $renderStrategySubform['plugins'][$type][$plugin_id]['#weight'] = -10;
      }

      if (!$allowedHtmlTags) {
        continue;
      }

      // Filters should only show based on whether they're enabled.
      if ($type === 'extension') {
        // If using the site-wide parser, then allowed HTML plugins that
        // reference disabled extensions there cannot be enable here.
        if ($siteWide) {
          $extensionDisabled = $defaultValue !== TRUE;
          $renderStrategySubform['plugins'][$type][$plugin_id]['#disabled'] = $extensionDisabled;
          if ($extensionDisabled) {
            $renderStrategySubform['plugins'][$type][$plugin_id]['#title'] = new FormattableMarkup('@title @disabled', [
              '@title' => $renderStrategySubform['plugins'][$type][$plugin_id]['#title'],
              '@disabled' => $this->t('(extension disabled)'),
            ]);
          }
        }
        else {
          $parents = array_merge(array_slice($renderStrategySubformState->createParents(), 0, -1), [
            'extensions', $plugin_id, 'enabled',
          ]);
          $selector = ':input[name="' . array_shift($parents) . '[' . implode('][', $parents) . ']"]';
          $renderStrategySubform['plugins'][$type][$plugin_id]['#title'] = new FormattableMarkup('@title @disabled', [
            '@title' => $renderStrategySubform['plugins'][$type][$plugin_id]['#title'],
            '@disabled' => $renderStrategySubformState->conditionalElement([
              '#value' => $this->t('(extension disabled)'),
            ], 'visible', $selector, ['checked' => FALSE]),
          ]);
          $renderStrategySubform['plugins'][$type][$plugin_id]['#states'] = [
            '!checked' => [$selector => ['checked' => FALSE]],
            'disabled' => [$selector => ['checked' => FALSE]],
          ];
        }
      }
      elseif ($type === 'filter') {
        $selector = ':input[name="filters[' . $plugin_id . '][status]"]';
        $renderStrategySubform['plugins'][$type][$plugin_id]['#title'] = new FormattableMarkup('@title @disabled', [
          '@title' => $renderStrategySubform['plugins'][$type][$plugin_id]['#title'],
          '@disabled' => $renderStrategySubformState->conditionalElement([
            '#value' => $this->t('(filter disabled)'),
          ], 'visible', $selector, ['checked' => FALSE]),
        ]);
        $renderStrategySubform['plugins'][$type][$plugin_id]['#states'] = [
          '!checked' => [$selector => ['checked' => FALSE]],
          'disabled' => [$selector => ['checked' => FALSE]],
        ];
      }
    }
    $renderStrategySubform['plugins']['#access'] = !!Element::getVisibleChildren($renderStrategySubform['plugins']);

    $renderStrategySubform['custom_allowed_html'] = [
      '#weight' => -10,
      '#type' => 'textarea',
      '#title' => $this->t('Custom Allowed HTML'),
      '#description' => $this->t('A list of additional custom allowed HTML tags that can be used. This follows the same rules as above; use cautiously and sparingly.'),
      '#default_value' => $renderStrategySubformState->getValue('custom_allowed_html', $parser->getCustomAllowedHtml()),
      '#attributes' => [
        'data-markdown-element' => 'custom_allowed_html',
      ],
    ];
    $renderStrategySubform['custom_allowed_html']['#description'] = $this->moreInfo($renderStrategySubform['custom_allowed_html']['#description'], RenderStrategyInterface::DOCUMENTATION_URL);
    FormTrait::resetToDefault($renderStrategySubform['custom_allowed_html'], 'custom_allowed_html', '', $renderStrategySubformState);
    $renderStrategySubformState->addElementState($renderStrategySubform['custom_allowed_html'], 'visible', 'type', ['value' => RenderStrategyInterface::FILTER_OUTPUT]);

    return $element;
  }

  /**
   * The AJAX callback used to return the parser ajax wrapper.
   */
  public static function ajaxChangeParser(array $form, FormStateInterface $form_state) {
    // Immediately return if subform parents aren't known.
    if (!($arrayParents = $form_state->get('markdownSubformArrayParents'))) {
      $arrayParents = array_slice($form_state->getTriggeringElement()['#array_parents'], 0, -2);
    }
    $subform = &NestedArray::getValue($form, $arrayParents);
    return $subform['ajax'];
  }

  /**
   * Retrieves configuration from an array of values.
   *
   * @param string $name
   *   The config name to use.
   * @param array $values
   *   An array of values.
   *
   * @return \Drupal\Core\Config\Config
   *   A Config object.
   *
   * @deprecated in markdown:8.x-2.0 and is removed from markdown:3.0.0.
   *   Use \Drupal\markdown\Form\SettingsForm::getConfigFromValues instead.
   * @see https://www.drupal.org/project/markdown/issues/3142418
   */
  public function getConfigFromValues($name, array $values) {
    $config = \Drupal::configFactory()->getEditable($name);

    // Some older 8.x-2.x code used to have the parser value as a string.
    // @todo Remove after 8.x-2.0 release.
    if (isset($values['parser']) && is_string($values['parser'])) {
      $values['id'] = $values['parser'];
      unset($values['parser']);
    }
    // Some older 8.x-2.x code used to have the parser value as an array.
    // @todo Remove after 8.x-2.0 release.
    elseif (isset($values['parser']) && is_array($values['parser'])) {
      $values += $values['parser'];
    }

    // Load the parser with the values so it can construct the proper config.
    $parser = $this->parserManager->createInstance($values['id'], $values);

    // Sort $configuration by using the $defaults keys. This ensures there
    // is a consistent order when saving the config.
    $configuration = $parser->getSortedConfiguration();

    $config->setData($configuration);

    return $config;
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
   *   Use \Drupal\markdown\Form\SettingsForm::getConfigFromValues instead.
   * @see https://www.drupal.org/project/markdown/issues/3142418
   */
  public function getConfigurationFromValues(array $values) {
    $config = $this->getConfigFromValues($this->settings->getName(), $values);
    return $config->get();
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->cleanValues()->getValues();

    // Normalize parser values into config data.
    $config = $this->getConfigFromValues($this->settings->getName(), $values);

    // Save the config.
    $config->save();

    // Invalidate any tags associated with the site-wide parser.
    if ($parserId = $config->get('id')) {
      $this->cacheTagsInvalidator->invalidateTags(["markdown.parser.$parserId"]);
    }

    drupal_set_message($this->t('The configuration options have been saved.'));
  }

  /**
   * Subform submission handler.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function submitSubform(array &$form, FormStateInterface $form_state) {
    // Immediately return if no subform parents or form hasn't submitted.
    if (!($arrayParents = $form_state->get('markdownSubformArrayParents'))|| !$form_state->isSubmitted()) {
      return;
    }
    $subform = &NestedArray::getValue($form, $arrayParents);
    $subformState = SubformState::createForSubform($subform, $form, $form_state);
    $parserId = $subformState->getValue(['parser', 'id']);
    if ($parserId && $this->parserManager->hasDefinition($parserId)) {
      $parser = $this->parserManager->createInstance($parserId, $subformState->getValue('parser', []));
      if ($parser instanceof SettingsInterface && $parser instanceof PluginFormInterface && !empty($subform['parser']['settings'])) {
        $parser->submitConfigurationForm($subform['parser']['settings'], SubformState::createForSubform($subform['parser']['settings'], $subform, $subformState));
      }
      if ($parser instanceof ExtensibleParserInterface && !empty($subform['parser']['extensions'])) {
        foreach ($parser->extensions() as $extensionId => $extension) {
          if ($extension instanceof SettingsInterface && $extension instanceof PluginFormInterface && isset($subform['parser']['extensions'][$extensionId]['settings'])) {
            $parser->submitConfigurationForm($subform['parser']['extensions'][$extensionId]['settings'], SubformState::createForSubform($subform['parser']['extensions'][$extensionId]['settings'], $subform, $subformState));
          }
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    $config = $this->getConfigFromValues($this->settings->getName(), $form_state->getValues());
    $typed_config = $this->typedConfigManager->createFromNameAndData('markdown.settings', $config->get());

    $violations = $typed_config->validate();
    foreach ($violations as $violation) {
      $form_state->setErrorByName(static::mapViolationPropertyPathsToFormNames($violation->getPropertyPath()), $violation->getMessage());
    }
  }

  protected static function mapViolationPropertyPathsToFormNames($property_path) {
    return str_replace('.', '][', $property_path);
  }

  /**
   * Subform validation handler.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function validateSubform(array &$form, FormStateInterface $form_state) {
    // Immediately return if no subform parents or form hasn't submitted.
    if (!($arrayParents = $form_state->get('markdownSubformArrayParents'))|| !$form_state->isSubmitted()) {
      return;
    }

    // Submit handlers aren't necessarily known until a user has started the.
    // process of submitting the form. The triggering element might have
    // specific submit handlers that needs to be intercepted and the only place
    // that this can be done is during the validation phase.
    if ($submitHandlers = $form_state->getSubmitHandlers()) {
      if (!in_array([$this, 'submitSubform'], $submitHandlers)) {
        array_unshift($submitHandlers, [$this, 'submitSubform']);
        $form_state->setSubmitHandlers($submitHandlers);
      }
    }
    else {
      $complete_form = &$form_state->getCompleteForm();
      $complete_form['#submit'][] = [$this, 'submitSubform'];
    }

    $subform = &NestedArray::getValue($form, $arrayParents);
    $subformState = SubformState::createForSubform($subform, $form, $form_state);
    $parserId = $subformState->getValue(['parser', 'id']);
    if ($parserId && $this->parserManager->hasDefinition($parserId)) {
      $parser = $this->parserManager->createInstance($parserId, $subformState->getValue('parser', []));
      if ($parser instanceof SettingsInterface && $parser instanceof PluginFormInterface && !empty($subform['parser']['settings'])) {
        $parser->validateConfigurationForm($subform['parser']['settings'], SubformState::createForSubform($subform['parser']['settings'], $subform, $subformState));
      }
      if ($parser instanceof ExtensibleParserInterface && !empty($subform['parser']['extensions'])) {
        foreach ($parser->extensions() as $extensionId => $extension) {
          if ($extension instanceof SettingsInterface && $extension instanceof PluginFormInterface && isset($subform['parser']['extensions'][$extensionId]['settings'])) {
            $extension->validateConfigurationForm($subform['parser']['extensions'][$extensionId]['settings'], SubformState::createForSubform($subform['parser']['extensions'][$extensionId]['settings'], $subform, $subformState));
          }
        }
      }
    }
  }

}
