<?php

namespace Drupal\markdown\Plugin\Markdown;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\Xss;
use Drupal\Core\Annotation\Translation;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Plugin\PluginBase;
use Drupal\Core\Render\Element;
use Drupal\Core\Render\Markup;
use Drupal\Core\Url;
use Drupal\filter\FilterFormatInterface;
use Drupal\markdown\MarkdownBenchmark;
use Drupal\markdown\Plugin\Filter\MarkdownFilterInterface;

/**
 * Class BaseMarkdownParser.
 *
 * @MarkdownParser(
 *   id = "_broken",
 *   label = @Translation("Missing Parser"),
 *   checkClass = "",
 * )
 */
class BaseMarkdownParser extends PluginBase implements MarkdownParserInterface {

  /**
   * Flag indicating whether this is currently in the process of a benchmark.
   *
   * @var bool
   */
  protected static $benchmark = FALSE;

  /**
   * The benchmarked parsed result.
   *
   * @var string|false
   */
  protected static $benchmarkParsedResult = FALSE;

  /**
   * MarkdownExtension plugins specific to a parser.
   *
   * @var array
   */
  protected static $extensions;

  /**
   * The current filter being used.
   *
   * @var \Drupal\markdown\Plugin\Filter\MarkdownFilterInterface
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
  protected $settings = [];

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    if (isset($configuration['filter'])) {
      $this->filter = $configuration['filter'];
      $this->filterId = $this->filter->getPluginId();
    }
    if (isset($configuration['settings'])) {
      $this->settings = $configuration['settings'];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function benchmark($markdown, $format = NULL) {
    static::$benchmark = TRUE;

    // Parse.
    $parsed_start = microtime(TRUE);
    $parsed_result = $this->parse($markdown);
    $parsed_end = microtime(TRUE);

    static::$benchmarkParsedResult = $parsed_result;

    // Render.
    if ($format === NULL) {
      $rendered_start = microtime(TRUE);
      $rendered_result = $this->render($markdown);
      $rendered_end = microtime(TRUE);
    }
    else {
      $rendered_start = microtime(TRUE);
      $rendered_result = check_markup($markdown, $format);
      $rendered_end = microtime(TRUE);
    }

    // Reset benchmark.
    static::$benchmark = FALSE;
    static::$benchmarkParsedResult = FALSE;

    return [
      // Parsed.
      MarkdownBenchmark::create($parsed_start, $parsed_end, $parsed_result),

      // Rendered.
      MarkdownBenchmark::create($rendered_start, $rendered_end, $rendered_result),

      // Total.
      MarkdownBenchmark::create($parsed_start, $rendered_end, $rendered_result),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildGuide(MarkdownFilterInterface $filter) {
    $build = $this->getSummary($filter);

    $build['help']['#markup'] = '<p>' . t('This site renders CommonMark Markdown. While learning all of the Markdown syntax may feel intimidating at first, learning how to use a very small number of the most basic Markdown syntax is very easy. The following sections will provide examples for commonly used Markdown syntax, what HTML output it generates and how it will display on the site.') . '</p>';

    $build['groups'] = ['#type' => 'vertical_tabs'];

    $base_url = Url::fromRoute('<front>', [], ['absolute' => TRUE])->toString();
    $site_name = \Drupal::config('system.site')->get('name');
    $site_mail = \Drupal::config('system.site')->get('mail');

    // Define default groups.
    $groups = [
      'general' => ['title' => t('General'), 'items' => []],
      'blockquotes' => ['title' => t('Block Quotes'), 'items' => []],
      'code' => ['title' => t('Code'), 'items' => []],
      'headings' => ['title' => t('Headings'), 'items' => []],
      'images' => ['title' => t('Images'), 'items' => []],
      'links' => ['title' => t('Links'), 'items' => []],
      'lists' => ['title' => t('Lists'), 'items' => []],
    ];

    // @codingStandardsIgnoreStart
    // Ignore Drupal coding standards during this section of code. There are
    // multiple concatenated t() strings that need to be ignored.

    // General.
    $groups['general']['items'][] = [
      'title' => t('Paragraphs'),
      'description' => t('Paragraphs are simply one or more consecutive lines of text, separated by one or more blank lines.'),
      'strip_p' => FALSE,
      'tags' => [
        'p' => [t('Paragraph one.') . "\n\n" . t('Paragraph two.')],
      ],
    ];
    $groups['general']['items'][] = [
      'title' => t('Line Breaks'),
      'description' => t('If you want to insert a <code>&lt;br /&gt;</code> break tag, end a line with two or more spaces, then type return.'),
      'strip_p' => FALSE,
      'tags' => [
        'br' => [t("Text with  \nline break")],
      ],
    ];
    $groups['general']['items'][] = [
      'title' => t('Horizontal Rule'),
      'tags' => [
        'hr' => ['---', '___', '***'],
      ],
    ];
    $groups['general']['items'][] = [
      'title' => t('Deleted text'),
      'description' => t('The CommonMark spec does not (yet) have syntax for <code>&lt;del&gt;</code> formatting. You must manually specify them.'),
      'tags' => [
        'del' => '<del>' . t('Deleted') . '</del>',
      ],
    ];
    $groups['general']['items'][] = [
      'title' => t('Emphasized text'),
      'tags' => [
        'em' => [
          '_' . t('Emphasized') . '_',
          '*' . t('Emphasized') . '*',
        ],
      ],
    ];
    $groups['general']['items'][] = [
      'title' => t('Strong text'),
      'tags' => [
        'strong' => [
          '__' . t('Strong', [], ['context' => 'Font weight']) . '__',
          '**' . t('Strong', [], ['context' => 'Font weight']) . '**',
        ],
      ],
    ];

    // Blockquotes.
    $groups['blockquotes']['items'][] = [
      'tags' => [
        'blockquote' => [
          '> ' . t("Block quoted") . "\n\n" . t("Normal text"),
          '> ' . t("Nested block quotes\n>> Nested block quotes\n>>> Nested block quotes\n>>>> Nested block quotes") . "\n\n" . t("Normal text"),
          '> ' . t("Lorem ipsum dolor sit amet, consectetur adipiscing elit. Lorem ipsum dolor sit amet, consectetur adipiscing elit. Lorem ipsum dolor sit amet, consectetur adipiscing elit.") . "\n\n" . t("Normal text"),
        ],
      ],
    ];

    // Code.
    $groups['code']['items'][] = [
      'title' => t('Inline code'),
      'tags' => [
        'code' => '`' . t('Coded') . '`',
      ],
    ];
    $groups['code']['items'][] = [
      'title' => t('Fenced code blocks'),
      'tags' => [
        'pre' => [
          "```\n" . t('Fenced code block') . "\n```",
          "~~~\n" . t('Fenced code block') . "\n~~~",
          "    " . t('Fenced code block - indented using 4+ spaces'),
          "\t" . t('Fenced code block - indented using tab'),
        ],
      ],
    ];
    $groups['code']['items'][] = [
      'title' => t('Fenced code blocks (using languages)'),
      'tags' => [
        'pre' => [
          "```css\n.selector {\n  color: #ff0;\n  font-size: 10px;\n  content: 'string';\n}\n```",
          "```js\nvar \$selector = \$('#id');\n\$selector.foo('bar', {\n  'baz': true,\n  'value': 1\n});\n```",
          "```php\n\$build['table'] = array(\n  '#theme' => 'table',\n  '#header' => \$header,\n  '#rows' => \$rows,\n  '#sticky' => FALSE,\n);\nprint drupal_render(\$build);\n```",
        ],
      ],
    ];

    // Headings.
    $groups['headings']['items'][] = [
      'tags' => [
        'h1' => '# ' . t('Heading 1'),
        'h2' => '## ' . t('Heading 2'),
        'h3' => '### ' . t('Heading 3'),
        'h4' => '#### ' . t('Heading 4'),
        'h5' => '##### ' . t('Heading 5'),
        'h6' => '###### ' . t('Heading 6'),
      ],
    ];

    // Images.
    $groups['images']['items'][] = [
      'title' => t('Images'),
      'tags' => [
        'img' => [
          '![' . t('Alt text') . '](http://lorempixel.com/400/200/ "' . t('Title text') . '")',
        ],
      ],
    ];
    $groups['images']['items'][] = [
      'title' => t('Referenced images'),
      'strip_p' => FALSE,
      'tags' => [
        'img' => [
          "Lorem ipsum dolor sit amet\n\n![" . t('Alt text') . "]\n\nLorem ipsum dolor sit amet\n\n[" . t('Alt text') . ']: http://lorempixel.com/400/200/ "' . t('Title text') . '"',
        ],
      ],
    ];

    // Links
    $groups['links']['items'][] = [
      'title' => t('Links'),
      'tags' => [
        'a' => [
          "<$base_url>",
          "[$site_name]($base_url)",
          "<$site_mail>",
          "[Email: $site_name](mailto:$site_mail)",
        ],
      ],
    ];
    $groups['links']['items'][] = [
      'title' => t('Referenced links'),
      'description' => t('Link references are very useful if you use the same words through out a document and wish to link them all to the same link.'),
      'tags' => [
        'a' => [
          "[$site_name]\n\n[$site_name]: $base_url \"" . t('My title') . '"',
          "Lorem ipsum [dolor] sit amet, consectetur adipiscing elit. Lorem ipsum [dolor] sit amet, consectetur adipiscing elit. Lorem ipsum [dolor] sit amet, consectetur adipiscing elit.\n\n[dolor]: $base_url \"" . t('My title') . '"',
        ],
      ],
    ];
    $groups['links']['items'][] = [
      'title' => t('Fragments (anchors)'),
      'tags' => [
        'a' => [
          "[$site_name]($base_url#fragment)",
          "[$site_name](#element-id)",
        ],
      ],
    ];

    // Lists.
    $groups['lists']['items'][] = [
      'title' => t('Ordered lists'),
      'tags' => [
        'ol' => [
          "1. " . t('First item') . "\n2. " . t('Second item') . "\n3. " . t('Third item') . "\n4. " . t('Fourth item'),
          "1) " . t('First item') . "\n2) " . t('Second item') . "\n3) " . t('Third item') . "\n4) " . t('Fourth item'),
          "1. " . t('All start with 1') . "\n1. " . t('All start with 1') . "\n1. " . t('All start with 1') . "\n1. " . t('Rendered with correct numbers'),
          "1. " . t('First item') . "\n2. " . t('Second item') . "\n   1. " . t('First nested item') . "\n   2. " . t('Second nested item') . "\n      1. " . t('Deep nested item'),
          "5. " . t('Start at fifth item') . "\n6. " . t('Sixth item') . "\n7. " . t('Seventh item') . "\n8. " . t('Eighth item'),
        ],
      ],
    ];
    $groups['lists']['items'][] = [
      'title' => t('Unordered lists'),
      'tags' => [
        'ul' => [
          "- " . t('First item') . "\n- " . t('Second item'),
          "- " . t('First item') . "\n- " . t('Second item') . "\n  - " . t('First nested item') . "\n  - " . t('Second nested item') . "\n    - " . t('Deep nested item'),
          "* " . t('First item') . "\n* " . t('Second item'),
          "+ " . t('First item') . "\n+ " . t('Second item'),
        ],
      ],
    ];
    // @codingStandardsIgnoreEnd

    // Allow enabled extensions to modify the build.
    foreach ($this->getExtensions() as $plugin_id => $extension) {
      if ($element = $extension->buildGuide($build)) {
        $build['extensions'][$plugin_id] = $element;
      }
    }

    // Iterate over all the items.
    $header = [
      Html::escape($filter->getLabel()) . ' / ' . t('HTML Output'),
      t('Rendered'),
    ];
    foreach ($groups as $group_name => $group) {
      // Build the group.
      $build['groups'][$group_name] = [
        '#title' => $group['title'],
        '#type' => 'fieldset',
      ];

      // Build group items.
      foreach ($group['items'] as $key => $item) {
        // Build the group item title.
        if (!empty($item['title'])) {
          $build['groups'][$group_name][$key]['title'] = [
            '#type' => 'html_tag',
            '#theme' => ['html_tag__commonmark_tip_title', 'html_tag'],
            '#tag' => 'h4',
            '#attributes' => ['class' => ['title']],
            '#value' => $item['title'],
          ];
        }

        // Build the group item description.
        if (!empty($item['description'])) {
          if (!is_array($item['description'])) {
            $item['description'] = [$item['description']];
          }
          if (count($item['description']) === 1) {
            $build['groups'][$group_name][$key]['description'] = [
              '#type' => 'html_tag',
              '#theme' => ['html_tag__commonmark_tip_description', 'html_tag'],
              '#tag' => 'div',
              '#attributes' => ['class' => ['description']],
              '#value' => $item['description'][0],
            ];
          }
          else {
            $build['groups'][$group_name][$key]['description'] = [
              '#theme' => [
                'item_list__commonmark_tip_description',
                'item_list',
              ],
              '#attributes' => ['class' => ['description']],
              '#items' => $item['description'],
            ];
          }
        }

        // Only continue if there are tags.
        if (empty($item['tags'])) {
          continue;
        }

        // Skip item if none of the tags are allowed.
        $item_tags = array_keys($item['tags']);
        $item_tags_not_allowed = array_diff($item_tags, $allowed_tags);
        if (count($item_tags_not_allowed) === count($item_tags)) {
          continue;
        }

        // Remove any tags not allowed.
        foreach ($item_tags_not_allowed as $tag) {
          unset($item['tags'][$tag]);
          unset($item['titles'][$tag]);
          unset($item['descriptions'][$tag]);
        }

        $rows = [];
        foreach ($item['tags'] as $tag => $examples) {
          if (!is_array($examples)) {
            $examples = [$examples];
          }
          foreach ($examples as $markdown) {
            $row = [];
            $rendered = (string) $this->render($markdown);
            if (!isset($item['strip_p']) || !empty($item['strip_p'])) {
              $rendered = preg_replace('/^<p>|<\/p>\n?$/', '', $rendered);
            }

            $row[] = [
              'data' => '<pre><code class="language-markdown">' . Html::escape($markdown) . '</code></pre><hr/><pre><code class="language-html">' . Html::escape($rendered) . '</code></pre>',
              'style' => 'padding-right: 2em; vertical-align: middle; width: 66.666%',
            ];
            $row[] = [
              'data' => $rendered,
              'style' => 'vertical-align: middle; width: 33.333%',
            ];

            $rows[] = $row;
          }
        }

        $build['groups'][$group_name][$key]['tags'] = [
          '#theme' => ['table__commonmark_tip', 'table'],
          '#header' => $header,
          '#rows' => $rows,
          '#sticky' => FALSE,
        ];
      }
    }

    // Remove empty groups.
    foreach (Element::children($build['groups']) as $child) {
      if (Element::children($build['groups'][$child])) {
        unset($build['groups'][$child]);
      }
    }

    $entities = [
      '&amp;' => t('Ampersand'),
      '&bull;' => t('Bullet'),
      '&cent;' => t('Cent'),
      '&copy;' => t('Copyright sign'),
      '&dagger;' => t('Dagger'),
      '&Dagger;' => t('Dagger (double)'),
      '&mdash;' => t('Dash (em)'),
      '&ndash;' => t('Dash (en)'),
      '&euro;' => t('Euro sign'),
      '&hellip;' => t('Horizontal ellipsis'),
      '&gt;' => t('Greater than'),
      '&lt;' => t('Less than'),
      '&middot;' => t('Middle dot'),
      '&nbsp;' => t('Non-breaking space'),
      '&para;' => t('Paragraph'),
      '&permil;' => t('Per mille sign'),
      '&pound;' => t('Pound sterling sign (GBP)'),
      '&reg;' => t('Registered trademark'),
      '&quot;' => t('Quotation mark'),
      '&trade;' => t('Trademark'),
      '&yen;' => t('Yen sign'),
    ];
    $rows = [];
    foreach ($entities as $entity => $description) {
      $rows[] = [
        $description,
        '<code>' . Html::escape($entity) . '</code>',
        $entity,
      ];
    }

    $build['groups']['entities'] = [
      '#title' => t('HTML Entities'),
      '#type' => 'fieldset',
      'description' => [
        '#markup' => '<p>' . t('Most unusual characters can be directly entered without any problems.') . '</p>' . '<p>' . t('If you do encounter problems, try using HTML character entities. A common example looks like &amp;amp; for an ampersand &amp; character. For a full list of entities see HTML\'s <a href="@html-entities">entities</a> page. Some of the available characters include:', ['@html-entities' => 'http://www.w3.org/TR/html4/sgml/entities.html']) . '</p>',
      ],
      'table' => [
        '#theme' => 'table',
        '#header' => [t('Entity'), t('HTML code'), t('Rendered')],
        '#rows' => $rows,
        '#sticky' => FALSE,
      ],
    ];

    $build['allowed_tags'] = [
      '#title' => t('Allowed HTML Tags'),
      '#type' => 'fieldset',
      '#group' => 'groups',
      '#weight' => 10,
      'tags' => $build['allowed_tags'],
    ];

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function getExtensions(MarkdownFilterInterface $filter = NULL, $enabled = TRUE) {
    if (!isset(static::$extensions[$this->pluginId])) {
      /** @var \Drupal\markdown\MarkdownExtensions $markdown_extensions */
      $markdown_extensions = \Drupal::service('plugin.manager.markdown.extension');
      static::$extensions[$this->pluginId] = !$filter || $filter->isEnabled() ? $markdown_extensions->getExtensions($this->pluginId, $enabled) : [];
    }

    /* @type \Drupal\markdown\Plugin\Markdown\Extension\MarkdownExtensionInterface $extension */
    foreach (static::$extensions[$this->pluginId] as $id => $extension) {
      if (isset($this->settings[$id])) {
        $extension->setSettings($this->settings[$id]);
      }
    }

    /** @var \Drupal\markdown\Plugin\Markdown\Extension\MarkdownExtensionInterface[] $extensions */
    $extensions = static::$extensions[$this->pluginId];
    return $extensions;
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
  public function getSummary(MarkdownFilterInterface $filter) {
    $build['summary'] = [
      '#type' => 'link',
      '#title' => $this->t('@icon Styling with Markdown is supported', [
        // Shamelessly copied from GitHub's Octicon icon set.
        // @todo Revisit this?
        // @see https://github.com/primer/octicons/blob/master/lib/svg/markdown.svg
        '@icon' => new FormattableMarkup('<svg class="octicon octicon-markdown v-align-bottom" viewBox="0 0 16 16" version="1.1" width="16" height="16" aria-hidden="true"><path fill-rule="evenodd" d="M14.85 3H1.15C.52 3 0 3.52 0 4.15v7.69C0 12.48.52 13 1.15 13h13.69c.64 0 1.15-.52 1.15-1.15v-7.7C16 3.52 15.48 3 14.85 3zM9 11H7V8L5.5 9.92 4 8v3H2V5h2l1.5 2L7 5h2v6zm2.99.5L9.5 8H11V5h2v3h1.5l-2.51 3.5z"></path></svg>', []),
      ]),
      '#url' => Url::fromRoute('filter.tips_all')
        ->setOptions(['attributes' => ['target' => '_blank']]),
    ];

    $allowed_tags = [];
    if (preg_match_all('/<([a-z0-9]+)[^a-z0-9]/i', $filter->getSetting('allowed_tags'), $matches)) {
      $allowed_tags = $matches[1];
    }

    $build['allowed_tags'] = [
      '#markup' => $allowed_tags ? t('<strong>Allowed HTML tags:</strong><br/><code>@tags</code>', ['@tags' => '<' . implode('> <', $allowed_tags) . '>']) : t('No HTML tags are allowed. CommonMark Markdown will be severely limited.'),
      '#weight' => 10,
    ];

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function getVersion() {
    return '';
  }

  /**
   * {@inheritdoc}
   */
  public function label($show_version = TRUE) {
    $variables['@label'] = $this->pluginDefinition['label'];
    $variables['@version'] = $show_version ? $this->getVersion() : '';
    return $variables['@version'] ? $this->t('@label (@version)', $variables) : $variables['@label'];
  }

  /**
   * {@inheritdoc}
   */
  public function parse($markdown, LanguageInterface $language = NULL) {
    return $markdown;
  }

  /**
   * {@inheritdoc}
   */
  public function render($markdown, LanguageInterface $language = NULL) {
    return Markup::create(Xss::filterAdmin(static::$benchmarkParsedResult ?: $this->parse($markdown, $language)));
  }

  /**
   * {@inheritdoc}
   */
  public function tips(MarkdownFilterInterface $filter, $long = FALSE) {
    $build = $long ? $this->buildGuide($filter) : $this->getSummary($filter);
    return \Drupal::service('renderer')->render($build);
  }

}
