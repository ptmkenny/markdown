<?php

namespace Drupal\markdown\Plugin\Markdown;

use Drupal\Component\Utility\NestedArray;
use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Annotation\Translation;
use Drupal\Core\Form\SubformStateInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Plugin\PluginBase;
use Drupal\Core\Url;
use Drupal\filter\FilterFormatInterface;
use Drupal\markdown\ParsedMarkdown;

/**
 * @MarkdownParser(
 *   id = "_broken",
 *   label = @Translation("Missing Parser"),
 * )
 */
class BaseParser extends PluginBase implements MarkdownParserInterface, MarkdownGuidelinesInterface {

  /**
   * The allowed HTML tags, if set.
   *
   * @var array
   */
  protected $allowedTags;

  /**
   * MarkdownExtension plugins specific to a parser.
   *
   * @var array
   */
  protected static $extensions;

  /**
   * The current filter being used.
   *
   * @var \Drupal\markdown_filter\Plugin\Filter\MarkdownFilterInterface
   */
  protected $filter;

  /**
   * The filter identifier.
   *
   * @var string
   */
  protected $filterId = '_default';

  /**
   * The parser settings.
   *
   * @var array
   */
  protected $settings;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->settings = isset($this->pluginDefinition['settings']) ? $this->pluginDefinition['settings'] : static::defaultSettings();
    if (isset($configuration['settings']) && is_array($configuration['settings'])) {
      $this->settings = NestedArray::mergeDeep($this->settings, $configuration['settings']);
    }
    if (isset($configuration['filter'])) {
      $this->filter = $configuration['filter'];
      $this->filterId = $this->filter->getPluginId();
      $this->settings = NestedArray::mergeDeep($this->settings, $this->filter->getSettings());
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public static function installed() {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public static function version() {
    return t('Not Installed');
  }

  /**
   * {@inheritdoc}
   */
  public function buildSettingsForm(array $element, SubformStateInterface $form_state) {
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function convertToHtml($markdown, LanguageInterface $language = NULL) {
    return $markdown;
  }

  /**
   * {@inheritdoc}
   */
  public function getFilter() {
    return $this->filter;
  }

  /**
   * {@inheritdoc}
   */
  public function getGuidelines() {
    $base_url = Url::fromRoute('<front>', [], ['absolute' => TRUE])->toString();
    $site_name = \Drupal::config('system.site')->get('name');

    // Define default groups.
    $guides = [
      'general' => ['title' => $this->t('General'), 'items' => []],
      'blockquotes' => ['title' => $this->t('Block Quotes'), 'items' => []],
      'code' => ['title' => $this->t('Code'), 'items' => []],
      'headings' => ['title' => $this->t('Headings'), 'items' => []],
      'images' => ['title' => $this->t('Images'), 'items' => []],
      'links' => ['title' => $this->t('Links'), 'items' => []],
      'lists' => ['title' => $this->t('Lists'), 'items' => []],
    ];

    // @codingStandardsIgnoreStart
    // Ignore Drupal coding standards during this section of code. There are
    // multiple concatenated $this->t() strings that need to be ignored.

    // General.
    $guides['general']['items'][] = [
      'title' => $this->t('Paragraphs'),
      'description' => $this->t('Paragraphs are simply one or more consecutive lines of text, separated by one or more blank lines.'),
      'strip_p' => FALSE,
      'tags' => [
        'p' => [t('Paragraph one.') . "\n\n" . $this->t('Paragraph two.')],
      ],
    ];
    $guides['general']['items'][] = [
      'title' => $this->t('Line Breaks'),
      'description' => $this->t('If you want to insert a <kbd>&lt;br /&gt;</kbd> break tag, end a line with two or more spaces, then type return.'),
      'strip_p' => FALSE,
      'tags' => [
        'br' => [t("Text with  \nline break")],
      ],
    ];
    $guides['general']['items'][] = [
      'title' => $this->t('Horizontal Rule'),
      'tags' => [
        'hr' => ['---', '___', '***'],
      ],
    ];
    $guides['general']['items'][] = [
      'title' => $this->t('Deleted text'),
      'description' => $this->t('The CommonMark spec does not (yet) have syntax for <kbd>&lt;del&gt;</kbd> formatting. You must manually specify them.'),
      'tags' => [
        'del' => '<del>' . $this->t('Deleted') . '</del>',
      ],
    ];
    $guides['general']['items'][] = [
      'title' => $this->t('Emphasized text'),
      'tags' => [
        'em' => [
          '_' . $this->t('Emphasized') . '_',
          '*' . $this->t('Emphasized') . '*',
        ],
      ],
    ];
    $guides['general']['items'][] = [
      'title' => $this->t('Strong text'),
      'tags' => [
        'strong' => [
          '__' . $this->t('Strong', [], ['context' => 'Font weight']) . '__',
          '**' . $this->t('Strong', [], ['context' => 'Font weight']) . '**',
        ],
      ],
    ];

    // Blockquotes.
    $guides['blockquotes']['items'][] = [
      'tags' => [
        'blockquote' => [
          '> ' . $this->t("Block quoted") . "\n\n" . $this->t("Normal text"),
          '> ' . $this->t("Nested block quotes\n>> Nested block quotes\n>>> Nested block quotes\n>>>> Nested block quotes") . "\n\n" . $this->t("Normal text"),
          '> ' . $this->t("Lorem ipsum dolor sit amet, consectetur adipiscing elit. Lorem ipsum dolor sit amet, consectetur adipiscing elit. Lorem ipsum dolor sit amet, consectetur adipiscing elit.") . "\n\n" . $this->t("Normal text"),
        ],
      ],
    ];

    // Code.
    $guides['code']['items'][] = [
      'title' => $this->t('Inline code'),
      'tags' => [
        'code' => '`' . $this->t('Inline code') . '`',
      ],
    ];
    $guides['code']['items'][] = [
      'title' => $this->t('Fenced code blocks'),
      'tags' => [
        'pre' => [
          "```\n" . $this->t('Fenced code block') . "\n```",
          "~~~\n" . $this->t('Fenced code block') . "\n~~~",
          "    " . $this->t('Fenced code block - indented using 4+ spaces'),
          "\t" . $this->t('Fenced code block - indented using tab'),
        ],
      ],
    ];
    $guides['code']['items'][] = [
      'title' => $this->t('Fenced code blocks (using languages)'),
      'tags' => [
        'pre' => [
          "```css\n.selector {\n  color: #ff0;\n  font-size: 10px;\n  content: 'string';\n}\n```",
          "```js\nvar \$selector = \$('#id');\n\$selector.foo('bar', {\n  'baz': true,\n  'value': 1\n});\n```",
          "```php\n\$build['table'] = array(\n  '#theme' => 'table',\n  '#header' => \$header,\n  '#rows' => \$rows,\n  '#sticky' => FALSE,\n);\nprint \Drupal::service('renderer')->renderPlain(\$build);\n```",
        ],
      ],
    ];

    // Headings.
    $guides['headings']['items'][] = [
      'tags' => [
        'h1' => '# ' . $this->t('Heading 1'),
        'h2' => '## ' . $this->t('Heading 2'),
        'h3' => '### ' . $this->t('Heading 3'),
        'h4' => '#### ' . $this->t('Heading 4'),
        'h5' => '##### ' . $this->t('Heading 5'),
        'h6' => '###### ' . $this->t('Heading 6'),
      ],
    ];

    // Images.
    $guides['images']['items'][] = [
      'title' => $this->t('Images'),
      'tags' => [
        'img' => [
          '![' . $this->t('Alt text') . '](http://lorempixel.com/400/200/ "' . $this->t('Title text') . '")',
        ],
      ],
    ];
    $guides['images']['items'][] = [
      'title' => $this->t('Referenced images'),
      'strip_p' => FALSE,
      'tags' => [
        'img' => [
          "Lorem ipsum dolor sit amet\n\n![" . $this->t('Alt text') . "]\n\nLorem ipsum dolor sit amet\n\n[" . $this->t('Alt text') . ']: http://lorempixel.com/400/200/ "' . $this->t('Title text') . '"',
        ],
      ],
    ];

    // Links
    $guides['links']['items'][] = [
      'title' => $this->t('Links'),
      'tags' => [
        'a' => [
          "<$base_url>",
          "[$site_name]($base_url)",
          "<john.doe@example.com>",
          "[Email: $site_name](mailto:john.doe@example.com)",
        ],
      ],
    ];
    $guides['links']['items'][] = [
      'title' => $this->t('Referenced links'),
      'description' => $this->t('Link references are very useful if you use the same words through out a document and wish to link them all to the same link.'),
      'tags' => [
        'a' => [
          "[$site_name]\n\n[$site_name]: $base_url \"" . $this->t('My title') . '"',
          "Lorem ipsum [dolor] sit amet, consectetur adipiscing elit.\nLorem ipsum [dolor] sit amet, consectetur adipiscing elit.\nLorem ipsum [dolor] sit amet, consectetur adipiscing elit.\n\n[dolor]: $base_url \"" . $this->t('My title') . '"',
        ],
      ],
    ];
    $guides['links']['items'][] = [
      'title' => $this->t('Fragments (anchors)'),
      'tags' => [
        'a' => [
          "[$site_name]($base_url#fragment)",
          "[$site_name](#element-id)",
        ],
      ],
    ];

    // Lists.
    $guides['lists']['items'][] = [
      'title' => $this->t('Ordered lists'),
      'tags' => [
        'ol' => [
          "1. " . $this->t('First item') . "\n2. " . $this->t('Second item') . "\n3. " . $this->t('Third item') . "\n4. " . $this->t('Fourth item'),
          "1) " . $this->t('First item') . "\n2) " . $this->t('Second item') . "\n3) " . $this->t('Third item') . "\n4) " . $this->t('Fourth item'),
          "1. " . $this->t('All start with 1') . "\n1. " . $this->t('All start with 1') . "\n1. " . $this->t('All start with 1') . "\n1. " . $this->t('Rendered with correct numbers'),
          "1. " . $this->t('First item') . "\n2. " . $this->t('Second item') . "\n   1. " . $this->t('First nested item') . "\n   2. " . $this->t('Second nested item') . "\n      1. " . $this->t('Deep nested item'),
          "5. " . $this->t('Start at fifth item') . "\n6. " . $this->t('Sixth item') . "\n7. " . $this->t('Seventh item') . "\n8. " . $this->t('Eighth item'),
        ],
      ],
    ];
    $guides['lists']['items'][] = [
      'title' => $this->t('Unordered lists'),
      'tags' => [
        'ul' => [
          "- " . $this->t('First item') . "\n- " . $this->t('Second item'),
          "- " . $this->t('First item') . "\n- " . $this->t('Second item') . "\n  - " . $this->t('First nested item') . "\n  - " . $this->t('Second nested item') . "\n    - " . $this->t('Deep nested item'),
          "* " . $this->t('First item') . "\n* " . $this->t('Second item'),
          "+ " . $this->t('First item') . "\n+ " . $this->t('Second item'),
        ],
      ],
    ];
    // @codingStandardsIgnoreEnd

    return $guides;
  }

  /**
   * {@inheritdoc}
   */
  public function getAllowedTags() {
    return $this->allowedTags;
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->pluginDefinition['description'] ?? NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getFilterFormat($format = NULL) {
    $default = filter_default_format();
    // Immediately return if filter is already an object.
    if ($format instanceof FilterFormatInterface) {
      return $format;
    }

    // Immediately return the default format if none was specified.
    if (!isset($format)) {
      return $default;
    }

    $formats = filter_formats();
    return isset($formats[$format]) ? $formats[$format] : $default;
  }

  /**
   * {@inheritdoc}
   */
  public function getLabel($version = TRUE) {
    if (!$version) {
      return $this->pluginDefinition['label'];
    }
    $variables['@label'] = $this->pluginDefinition['label'];
    $variables['@version'] = $this->getVersion();
    return $variables['@version'] ? $this->t('@label (@version)', $variables) : $variables['@label'];
  }

  /**
   * {@inheritdoc}
   */
  public function getSetting($name) {
    return isset($this->settings[$name]) ? $this->settings[$name] : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getSettings() {
    return $this->settings;
  }

  /**
   * {@inheritdoc}
   */
  public function getSummary() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getUrl() {
    $url = $this->pluginDefinition['url'] ?? NULL;
    if ($url && UrlHelper::isExternal($url)) {
      return Url::fromUri($url)->setOption('attributes', ['target' => '_blank']);
    }
    return $url ? Url::fromUserInput($url) : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getVersion() {
    return $this->pluginDefinition['version'];
  }

  /**
   * {@inheritdoc}
   */
  public function isInstalled() {
    return $this->pluginDefinition['installed'];
  }

  /**
   * {@inheritdoc}
   */
  public function parse($markdown, LanguageInterface $language = NULL) {
    return ParsedMarkdown::create($markdown, $this->convertToHtml($markdown, $language), $language);
  }

  /**
   * {@inheritdoc}
   */
  public function setAllowedTags(array $tags = []) {
    $this->allowedTags = $tags;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setSetting($name, $value = NULL) {
    $this->settings[$name] = $value;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setSettings(array $settings = []) {
    $this->settings = $settings;
  }

}
