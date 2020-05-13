<?php

namespace Drupal\markdown\Form;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Cache\CacheTagsInvalidatorInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Plugin\PluginDependencyTrait;
use Drupal\Core\Plugin\PluginFormInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\Render\ElementInfoManagerInterface;
use Drupal\Core\Render\Markup;
use Drupal\Core\Url;
use Drupal\markdown\Config\MarkdownConfig;
use Drupal\markdown\MarkdownInterface;
use Drupal\markdown\Plugin\Markdown\ExtensibleParserInterface;
use Drupal\markdown\Plugin\Markdown\ParserInterface;
use Drupal\markdown\Plugin\Markdown\RenderStrategyInterface;
use Drupal\markdown\Plugin\Markdown\SettingsInterface;
use Drupal\markdown\PluginManager\AllowedHtmlManager;
use Drupal\markdown\PluginManager\ParserManagerInterface;
use Drupal\markdown\Traits\FilterAwareTrait;
use Drupal\markdown\Traits\FormTrait;
use Drupal\markdown\Util\FilterAwareInterface;
use Drupal\markdown\Util\FilterFormatAwareInterface;
use Drupal\markdown\Util\FilterHtml;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Markdown Settings Form.
 */
class SettingsForm extends FormBase implements FilterAwareInterface {

  use FilterAwareTrait;
  use FormTrait;
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

  /**
   * MarkdownSettingsForm constructor.
   *
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
  public function __construct(CacheTagsInvalidatorInterface $cacheTagsInvalidator, ElementInfoManagerInterface $elementInfo, MarkdownInterface $markdown, ParserManagerInterface $parserManager, MarkdownConfig $settings) {
    $this->cacheTagsInvalidator = $cacheTagsInvalidator;
    $this->elementInfo = $elementInfo;
    $this->markdown = $markdown;
    $this->parserManager = $parserManager;
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
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form += [
      '#parents' => [],
      '#title' => $this->t('Markdown'),
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
    $siteWideSettingsForm = static::siteWideSettingsForm();

    // Immediately return if there are no installed parsers.
    if (!$this->parserManager->getDefinitions(FALSE)) {
      $element['missing'] = [
        '#type' => 'item',
        '#title' => $this->t('No markdown parsers installed.'),
      ];

      $systemStatus = Url::fromRoute('system.status', [], ['fragment' => 'markdown']);
      if ($systemStatus->access()) {
        $element['missing']['#description'] = $this->t('Visit the <a href=":system.status" target="_blank">@system.status</a> page for more details.', [
          '@system.status' => $this->t('Status report'),
          ':system.status' => $systemStatus->toString(),
        ]);
      }
      else {
        $element['missing']['#description'] = $this->t('Ask your site administrator to install a supported markdown parser.');
      }

      // Hide the actions on the site-wide settings form.
      if ($siteWideSettingsForm) {
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

    // Add the markdown.admin library to update summaries in vertical tabs.
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
    // If the triggering element is the parser select element, clear out any
    // parser values other than the identifier. This is necessary since the
    // parser has switched and the previous parser settings may not translate
    // correctly to the new parser.
    $trigger = $form_state->getTriggeringElement();
    if ($trigger && isset($trigger['#ajax']['callback']) && $trigger['#ajax']['callback'] = '\Drupal\markdown\Form\SettingsForm::ajaxChangeParser' && ($parserId = $form_state->getValue(['parser', 'id']))) {
      $parents = $form_state->createParents();
      $input = &NestedArray::getValue($form_state->getUserInput(), $parents);
      $values = &NestedArray::getValue($form_state->getValues(), $parents);
      $input['parser'] = ['id' => $parserId];
      $values['parser'] = ['id' => $parserId];
    }

    $markdownAjaxId = $form_state->get('markdownAjaxId');
    $markdownGroup = $form_state->get('markdownGroup');
    $labels = $this->parserManager->labels();

    // Include a "Site-wide parser" option if not on the global settings page.
    if ($includeSiteWideOption = !static::siteWideSettingsForm()) {
      $siteWideParser = $this->t('Site-wide parser (@parser)', [
        '@parser' => $this->markdown->getParser()->getLabel(),
      ]);
      $labels = array_merge(['' => $siteWideParser], $labels);
    }

    $parents = isset($element['#parents']) ? $element['#parents'] : [];
    $configuration = NestedArray::mergeDeep($this->settings->get('parser'), $form_state->getValue('parser', []));

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
    $parserId = $parserSubform->getValue('id', $this->settings->get('parser.id'));

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

    if ($includeSiteWideOption) {
      if (($markdownSettingsUrl = Url::fromRoute('markdown.settings')) && $markdownSettingsUrl->access()) {
        $parserElement['id']['#description'] = $this->t('Site-wide markdown settings can be adjusted by visiting the <a href=":markdown.settings" target="_blank">@markdown.settings</a> page.', [
          '@markdown.settings' => $this->t('Markdown Settings'),
          ':markdown.settings' => $markdownSettingsUrl->toString(),
        ]);
      }
      else {
        $parserElement['id']['#description'] = $this->t('Site-wide markdown settings can only be adjusted by administrators.');
      }
    }

    // If there's no set parser identifier, then it's the global parser.
    if (!$parserId) {
      // Load global parser.
      $parser = $this->markdown->getParser();
      if ($parser instanceof FilterAwareInterface && ($filter = $this->getFilter())) {
        $parser->setFilter($filter);
      }

      // Build render strategy (which may allow filters).
      $parserElement = $this->buildRenderStrategy($parser, $parserElement, $parserSubform);
      return $element;
    }

    // Retrieve the parser.
    $parser = $this->parserManager->createInstance($parserId, $configuration);
    if ($parser instanceof FilterAwareInterface && ($filter = $this->getFilter())) {
      $parser->setFilter($filter);
    }

    // Indicate whether parser is installed.
    $this->addDataAttribute($parserElement['id'], 'markdownInstalled', $parser->isInstalled());

    // Add the parser description.
    $descriptions = [];
    if ($description = $parser->getDescription()) {
      $descriptions[] = $description;
    }
    if ($url = $parser->getUrl()) {
      $descriptions[] = Link::fromTextAndUrl($this->t('[More Info]'), $url)->toString();
    }
    $parserElement['id']['#description'] = Markup::create(implode(' ', $descriptions));

    // Build render strategy.
    $parserElement = $this->buildRenderStrategy($parser, $parserElement, $parserSubform);

    // Build parser settings.
    $parserElement = $this->buildParserSettings($parser, $parserElement, $parserSubform);

    // Build parser extensions.
    $parserElement = $this->buildParserExtensions($parser, $parserElement, $parserSubform);

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

    // Only show settings if there are settings (excluding render_strategy).
    $children = Element::getVisibleChildren($element['settings']);
    if ($children === ['render_strategy']) {
      $element['settings']['#type'] = 'container';
    }
    $element['settings']['#access'] = !!$children;

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

    // Add any specific extension settings.
    foreach ($extensions as $extensionId => $extension) {
      $label = $extension->getLabel();

      $element['extensions'][$extensionId] = [
        '#type' => 'details',
        '#title' => $label,
        '#group' => $markdownGroup,
        '#parents' => array_merge($parents, ['extensions', $extensionId]),
      ];
      $extensionElement = &$element['extensions'][$extensionId];
      $extensionSubform = SubformState::createForSubform($element['extensions'][$extensionId], $element, $form_state);

      $bundled = in_array($extensionId, $parser->getBundledExtensionIds(), TRUE);
      $installed = $extension->isInstalled();
      $enabled = $extensionSubform->getValue('enabled', $extension->isEnabled());

      $descriptions = [];
      if ($description = $extension->getDescription()) {
        $descriptions[] = $description;
      }
      if ($url = $extension->getUrl()) {
        $descriptions[] = Link::fromTextAndUrl($this->t('[More Info]'), $url)->toString();
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
        '#description' => Markup::create(implode(' ', $descriptions)),
        '#default_value' => $bundled || $enabled,
        '#disabled' => $bundled || !$installed,
      ]);

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
   *
   * @return array
   *   The $element passed, modified to include the render strategy elements.
   */
  protected function buildRenderStrategy(ParserInterface $parser, array $element, SubformStateInterface $form_state) {
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
      '#description' => $this->t('Determines the strategy to use when dealing with user provided HTML markup. <a href=":markdown_xss" target="_blank">[More Info]</a>', [
        ':markdown_xss' => RenderStrategyInterface::MARKDOWN_XSS_URL,
      ]),
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

    // Build allowed HTML plugins.
    $renderStrategySubform['plugins'] = [
      '#weight' => -10,
      '#type' => 'item',
      '#input' => FALSE,
      '#title' => $this->t('Allowed HTML'),
      '#description_display' => 'before',
      '#description' => $this->t('The following are registered <code>@MarkdownAllowedHtml</code> plugins provided by various filters, modules, themes, parser and their extensions (if supported) that allow additional HTML tags and attributes based on your current site configuration.<br><br>The goal here is to start of with a small list and and only allow HTML tags or attributes when they are actually needed.<br><br>It is likely that these are required to ensure continued functionality with various supported features, however you may turn them off if you feel they represent a security risk.<br><br>Each plugin displays the list of HTML tags that are allowed to be used. If a tag has not specified any attributes, then only the tag may be used and any attributes encountered will be stripped; each HTML tag must explicitly specify its allowed attributes. It may also specify a global HTML tag using an asterisk as the tag name (<code>&lt;*&gt;</code>) to provide additional global attributes. This means that these attributes will work on any tag and do not need to be specified explicitly for other tags.<br><br>If an attribute is by itself, then all values are allowed. If it specifies a space delimited list of values, then only those values are allowed. Attribute names or values may also be written as a prefix and wildcard like <code>jump-*</code>.<br><br>JavaScript event attributes (<code>on*</code>), JavaScript URLs (<code>javascript://</code>),and the CSS attributes (<code>style</code>) are always stripped.'),
    ];
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
          $renderStrategySubform['plugins'][$type]['#title'] = $this->t('Parser Extensions');
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
        // Form value.
        $defaultValue = $renderStrategySubformState->getValue(['plugins', $plugin_id]);
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
        '#default_value' => $defaultValue,
        '#attributes' => [
          'data-markdown-default-value' => $defaultValue ? 'true' : 'false',
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
        $parents = array_merge(array_slice($renderStrategySubformState->createParents(), 0, -1), [
          'extensions', $plugin_id, 'enabled',
        ]);
        $selector = ':input[name="' . array_shift($parents) . '[' . implode('][', $parents) . ']"]';
        $renderStrategySubform['plugins'][$type][$plugin_id]['#title'] = new FormattableMarkup('@title @disabled', [
          '@title' => $renderStrategySubform['plugins'][$type][$plugin_id]['#title'],
          '@disabled' => $renderStrategySubformState->conditionalElement([
            '#value' => $this->t('(not used, extension disabled)'),
          ], 'visible', $selector, ['checked' => FALSE]),
        ]);
        $renderStrategySubform['plugins'][$type][$plugin_id]['#states'] = [
          '!checked' => [$selector => ['checked' => FALSE]],
          'disabled' => [$selector => ['checked' => FALSE]],
        ];
      }
      elseif ($type === 'filter') {
        $selector = ':input[name="filters[' . $plugin_id . '][status]"]';
        $renderStrategySubform['plugins'][$type][$plugin_id]['#title'] = new FormattableMarkup('@title @disabled', [
          '@title' => $renderStrategySubform['plugins'][$type][$plugin_id]['#title'],
          '@disabled' => $renderStrategySubformState->conditionalElement([
            '#value' => $this->t('(not used, filter disabled)'),
          ], 'visible', $selector, ['checked' => FALSE]),
        ]);
        $renderStrategySubform['plugins'][$type][$plugin_id]['#states'] = [
          '!checked' => [$selector => ['checked' => FALSE]],
          'disabled' => [$selector => ['checked' => FALSE]],
        ];
      }
    }
    $renderStrategySubform['plugins']['#access'] = !!Element::getVisibleChildren($renderStrategySubform['plugins']);

    $renderStrategySubform['allowed_html'] = [
      '#weight' => -10,
      '#type' => 'textarea',
      '#title' => $this->t('Custom Allowed HTML'),
      '#description' => $this->t('A list of custom HTML tags that can be used. This follows the same rules as above; use cautiously and sparingly. The only time you need to supply the same tags as above is if you intend to disable the plugin and instead manually provide it here. The goal is the same as above: only allow HTML tags and attributes when they are needed. For example: specifying wide reaching attributes like <code>&lt;* data-*&gt;</code> would allow any data attribute to be used on any HTML tag. This kind of "open ended" permission can make your site extremely vulnerable to potential attacks and future unknown exploits due to this attribute namespaces frequent interactions with JavaScript. Instead, it is highly recommended that you only allow specific, known, data attributes that are actively used by libraries on your site. For maximum and long term viability, it is recommended that you create your own custom <code>@MarkdownAllowedHtml</code> plugins as the needs arise.'),
      '#default_value' => $renderStrategySubformState->getValue('allowed_html', $parser->getAllowedHtml()),
      '#attributes' => [
        'data-markdown-element' => 'allowed_html',
      ],
    ];
    FormTrait::resetToDefault($renderStrategySubform['allowed_html'], 'allowed_html', '', $renderStrategySubformState);
    $renderStrategySubformState->addElementState($renderStrategySubform['allowed_html'], 'visible', 'type', ['value' => RenderStrategyInterface::FILTER_OUTPUT]);

    return $element;
  }

  /**
   * The AJAX callback used to return the parser ajax wrapper.
   */
  public static function ajaxChangeParser(array $form, FormStateInterface $form_state) {
    $form_state->setRebuild(TRUE);
    // Immediately return if subform parents aren't known.
    if (!($arrayParents = $form_state->get('markdownSubformArrayParents'))) {
      $arrayParents = array_slice($form_state->getTriggeringElement()['#array_parents'], 0, -2);
    }
    $subform = &NestedArray::getValue($form, $arrayParents);
    return $subform['ajax'];
  }

  /**
   * Retrieves configuration from values.
   *
   * @param array $values
   *   An array of values.
   *
   * @return array
   *   The configuration array.
   */
  public function getConfigurationFromValues(array $values) {
    $defaults = [
      'id' => NULL,
      'render_strategy' => [],
      'settings' => [],
      'extensions' => [],
    ];
    $pluginConfiguration = (isset($values['parser']) ? $values['parser'] : $values) + $defaults;

    $parser = $this->parserManager->createInstance($pluginConfiguration['id'], $pluginConfiguration);
    $configuration = $parser->getConfiguration();

    // Sort $configuration by using the $defaults keys. This ensures there
    // is a consistent order when saving the config.
    $configuration = array_replace(array_flip(array_keys(array_intersect_key($defaults, $configuration))), $configuration);

    $this->calculatePluginDependencies($parser);

    return [
      'dependencies' => $this->dependencies,
      'parser' => $configuration,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->cleanValues()->getValues();

    // Normalize parser values into config data.
    $configuration = $this->getConfigurationFromValues($values);

    $this->settings
      ->setData($configuration)
      ->save();

    if ($parserId = $this->settings->get('parser.id')) {
      $this->cacheTagsInvalidator->invalidateTags(["markdown.parser:$parserId"]);
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
