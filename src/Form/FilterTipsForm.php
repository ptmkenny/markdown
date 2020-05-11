<?php

namespace Drupal\markdown\Form;

use Drupal\Component\Utility\Html;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\Render\Markup;
use Drupal\markdown\Plugin\Markdown\MarkdownGuidelinesAlterInterface;
use Drupal\markdown\Plugin\Markdown\ParserInterface;
use Drupal\markdown\Util\FilterHtml;

/**
 * Custom form for display markdown tips.
 */
class FilterTipsForm extends FormBase {

  /**
   * A Markdown Parser.
   *
   * @var \Drupal\markdown\Plugin\Markdown\ParserInterface
   */
  protected $parser;

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'markdown_filter_tips';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $long = FALSE, ParserInterface $parser = NULL) {
    if (!$parser) {
      return [];
    }

    $this->parser = $parser;

    if ($long) {
      $guidelines = $parser->getGuidelines();
      if ($parser instanceof MarkdownGuidelinesAlterInterface) {
        $parser->alterGuidelines($guidelines);
      }
      $form = $this->buildGuidelines($guidelines);
    }
    else {
      $form['description']['#markup'] = $parser->getDescription();
    }
    $form['#type'] = 'container';
    return $form;
  }

  /**
   * Builds guidelines.
   */
  protected function buildGuidelines(array $guides = []) {
    $allowedHtmlTags = FilterHtml::fromParser($this->parser)->getAllowedTags();

    $build['tips'] = ['#type' => 'vertical_tabs'];

    $firstGuide = current(array_keys($guides));
    $build['guides'][$firstGuide]['description'] = [
      '#weight' => -10,
      '#markup' => Markup::create(sprintf("<p>%s</p>", $this->t('This site renders @label markdown. @description', [
        '@label' => $this->parser->getLabel(FALSE),
        '@description' => $this->parser->getDescription(),
      ]))),
    ];
    $build['guides'][$firstGuide]['help'] = [
      '#weight' => -10,
      '#markup' => Markup::create(sprintf("<p>%s</p>", $this->t('While learning all of the Markdown syntax may feel intimidating at first, learning how to use a very small number of the most basic Markdown syntax is very easy. The following sections will provide examples for commonly used Markdown syntax, what HTML output it generates and how it will display on the site.'))),
    ];

    // Iterate over all the items.
    $header = [
      Html::escape($this->t('Markdown')),
      sprintf("%s / %s", $this->t('HTML Output'), $this->t('Rendered')),
    ];
    foreach ($guides as $guide_id => $guide) {
      // Build the guide.
      $build['guides'][$guide_id]['#title'] = $guide['title'];
      $build['guides'][$guide_id]['#type'] = 'fieldset';
      $build['guides'][$guide_id]['#group'] = 'tips';
      if (isset($guide['description'])) {
        $build['guides'][$guide_id]['#description'] = $guide['description'];
      }

      // Build guide items.
      foreach ($guide['items'] as $key => $item) {
        // Build the guide item title.
        if (!empty($item['title'])) {
          $build['guides'][$guide_id][$key]['title'] = [
            '#type' => 'html_tag',
            '#theme' => ['html_tag__markdown_tip_title', 'html_tag'],
            '#tag' => 'h4',
            '#attributes' => ['class' => ['title']],
            '#value' => $item['title'],
          ];
        }

        // Build the guide item description.
        if (!empty($item['description'])) {
          if (!is_array($item['description'])) {
            $item['description'] = [$item['description']];
          }
          if (count($item['description']) === 1) {
            $build['guides'][$guide_id][$key]['description'] = [
              '#type' => 'html_tag',
              '#theme' => ['html_tag__markdown_tip_description', 'html_tag'],
              '#tag' => 'div',
              '#attributes' => ['class' => ['description']],
              '#value' => $item['description'][0],
            ];
          }
          else {
            $build['guides'][$guide_id][$key]['description'] = [
              '#theme' => [
                'item_list__markdown_tip_description',
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
        $item_tags_not_allowed = array_diff($item_tags, $allowedHtmlTags ?: $item_tags);
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
            $rendered = (string) $this->convertToHtml($markdown);
            if (!isset($item['strip_p']) || !empty($item['strip_p'])) {
              $rendered = preg_replace('/^<p>|<\/p>\n?$/', '', $rendered);
            }

            $row[] = [
              'data' => Markup::create('<pre><code class="language-markdown">' . Html::escape($markdown) . '</code></pre>'),
              'style' => 'padding-right: 2em; width: 50%',
            ];
            $row[] = [
              'data' => Markup::create('<pre><code class="language-html">' . Html::escape($rendered) . '</code></pre>' . $rendered),
              'style' => 'width: 50%',
            ];

            $rows[] = $row;
          }
        }

        $build['guides'][$guide_id][$key]['tags'] = [
          '#theme' => 'table__markdown_tips',
          '#header' => $header,
          '#rows' => $rows,
          '#sticky' => FALSE,
        ];
      }
    }

    // Remove empty guides.
    foreach (Element::children($build['guides']) as $child) {
      if (!Element::getVisibleChildren($build['guides'][$child])) {
        unset($build['guides'][$child]);
      }
    }

    $entities = [
      '&amp;' => $this->t('Ampersand'),
      '&bull;' => $this->t('Bullet'),
      '&cent;' => $this->t('Cent'),
      '&copy;' => $this->t('Copyright sign'),
      '&dagger;' => $this->t('Dagger'),
      '&Dagger;' => $this->t('Dagger (double)'),
      '&mdash;' => $this->t('Dash (em)'),
      '&ndash;' => $this->t('Dash (en)'),
      '&euro;' => $this->t('Euro sign'),
      '&hellip;' => $this->t('Horizontal ellipsis'),
      '&gt;' => $this->t('Greater than'),
      '&lt;' => $this->t('Less than'),
      '&middot;' => $this->t('Middle dot'),
      '&nbsp;' => $this->t('Non-breaking space'),
      '&para;' => $this->t('Paragraph'),
      '&permil;' => $this->t('Per mille sign'),
      '&pound;' => $this->t('Pound sterling sign (GBP)'),
      '&reg;' => $this->t('Registered trademark'),
      '&quot;' => $this->t('Quotation mark'),
      '&trade;' => $this->t('Trademark'),
      '&yen;' => $this->t('Yen sign'),
    ];
    $rows = [];
    foreach ($entities as $entity => $description) {
      $rows[] = [
        $description,
        ['data' => Markup::create('<kbd>' . Html::escape($entity) . '</kbd>')],
        ['data' => Markup::create($entity)],
      ];
    }

    $build['guides']['entities'] = [
      '#title' => $this->t('HTML Entities'),
      '#type' => 'fieldset',
      '#group' => 'tips',
      'description' => [
        '#markup' => '<p>' . $this->t('Most unusual characters can be directly entered without any problems.') . '</p>' . '<p>' . $this->t('If you do encounter problems, try using HTML character entities. A common example looks like &amp;amp; for an ampersand &amp; character. For a full list of entities see HTML\'s <a href="@html-entities">entities</a> page. Some of the available characters include:', ['@html-entities' => 'http://www.w3.org/TR/html4/sgml/entities.html']) . '</p>',
      ],
      'table' => [
        '#theme' => 'table__markdown_tips',
        '#header' => [
          $this->t('HTML Entity'),
          $this->t('HTML code'),
          $this->t('Rendered'),
        ],
        '#rows' => $rows,
        '#sticky' => FALSE,
      ],
    ];

    // Retrieve the HTML filter for this parser.
    $markdownHtmlFilter = FilterHtml::fromParser($this->parser);

    // Retrieve the list of allowed HTML, stripping any "global" elements.
    $allowedHtml = Html::escape($markdownHtmlFilter->getAllowedHtml(FALSE));

    // Retrieve the list of allowed global attributes.
    $allowedGlobalAttributes = implode(', ', array_keys(array_filter($markdownHtmlFilter->getHTMLRestrictions()['allowed']['*'])));

    $build['allowed_html'] = [
      '#title' => $this->t('Allowed HTML Tags'),
      '#access' => !!$allowedHtmlTags,
      '#type' => 'fieldset',
      '#group' => 'tips',
      '#weight' => 10,
      'html' => [
        '#markup' => Markup::create(sprintf("<p>%s</p><pre><code>%s</code></pre>", $this->t('List of allowed HTML tags and their attributes:'), $allowedHtml)),
      ],
      'global_attributes' => [
        '#markup' => Markup::create(sprintf("<p>%s</p><pre><code>%s</code></pre>", $this->t('List of allowed global attributes that can be used on any of the above HTML tags:'), $allowedGlobalAttributes)),
      ],
    ];

    return $build;
  }

  /**
   * Helper method to directly invoke parser.
   *
   * @param string $markdown
   *   The markdown to convert to HTML.
   * @param \Drupal\Core\Language\LanguageInterface $language
   *   Optional. The language of the markdown to be parsed.
   *
   * @return string
   *   The generated HTML from parsing $markdown.
   *
   * @noinspection PhpDocMissingThrowsInspection
   */
  protected function convertToHtml($markdown, LanguageInterface $language = NULL) {
    static $convertToHtml;
    if (!isset($convertToHtml)) {
      /* @noinspection PhpUnhandledExceptionInspection */
      $convertToHtml = new \ReflectionMethod($this->parser, 'convertToHtml');
      $convertToHtml->setAccessible(TRUE);
    }
    return $convertToHtml->invoke($this->parser, $markdown, $language);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Intentionally left blank. This form doesn't actually submit anything.
  }

}
