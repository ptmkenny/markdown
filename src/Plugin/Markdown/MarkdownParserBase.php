<?php

namespace Drupal\markdown\Plugin\Markdown;

use Drupal\Core\Form\SubformStateInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Url;
use Drupal\markdown\Config\ImmutableMarkdownSettings;
use Drupal\markdown\ParsedMarkdown;
use Drupal\markdown\ParsedMarkdownInterface;
use Drupal\markdown\Traits\MarkdownPluginSettingsTrait;

/**
 * @property \Drupal\markdown\Config\ImmutableMarkdownSettings $config
 */
abstract class MarkdownParserBase extends MarkdownInstallablePluginBase implements MarkdownParserInterface, MarkdownGuidelinesInterface, MarkdownPluginSettingsInterface {

  use MarkdownPluginSettingsTrait {
    buildSettingsForm as traitBuildSettingsForm;
    defaultSettings as traitDefaultSettings;
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'drupal_allowed_html' => ParsedMarkdownInterface::ALLOWED_HTML,
    ] + static::traitDefaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function buildSettingsForm(array $element, SubformStateInterface $form_state) {
    $element = $this->traitBuildSettingsForm($element, $form_state);

    $element['drupal_allowed_html'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Allowed HTML tags'),
      '#default_value' => $form_state->getValue('drupal_allowed_html', $this->getSetting('drupal_allowed_html', ParsedMarkdownInterface::ALLOWED_HTML)),
      '#description' => $this->t('A list of HTML tags that can be used. By default only the <em>lang</em> and <em>dir</em> attributes are allowed for all HTML tags. Each HTML tag may have attributes which are treated as allowed attribute names for that HTML tag. Each attribute may allow all values, or only allow specific values. Attribute names or values may be written as a prefix and wildcard like <em>jump-*</em>. JavaScript event attributes, JavaScript URLs, and CSS are always stripped.'),
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function convertToHtml($markdown, LanguageInterface $language = NULL) {
    return $markdown;
  }

  /**
   * Retrieves the allowed tags.
   *
   * @return array
   *   An array of allowed HTML tags that are permitted in the parsed HTML
   *   to ensure it is safe from XSS vulnerabilities.
   */
  public function getAllowedHtml() {
    return $this->getSetting('drupal_allowed_html', ParsedMarkdownInterface::ALLOWED_HTML);
  }

  /**
   * {@inheritdoc}
   */
  protected function getConfigClass() {
    return ImmutableMarkdownSettings::class;
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
  public function getSummary() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function parse($markdown, LanguageInterface $language = NULL) {
    $parsed = ParsedMarkdown::create($markdown, $this->convertToHtml($markdown, $language), $language);
    if ($this->xssFilter()) {
      $parsed->setAllowedHtml($this->getAllowedHtml());
    }
    return $parsed;
  }

  /**
   * Indicates whether allowed tags should be used to XSS filter.
   *
   * @return bool
   */
  protected function xssFilter() {
    return TRUE;
  }

}
