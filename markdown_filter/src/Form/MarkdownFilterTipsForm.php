<?php

namespace Drupal\markdown_filter\Form;

use Drupal\Component\Utility\Html;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\Render\Markup;
use Drupal\markdown\Plugin\Markdown\MarkdownGuidelinesAlterInterface;
use Drupal\markdown\Plugin\Markdown\MarkdownParserInterface;

class MarkdownFilterTipsForm extends FormBase {

  /**
   * @var \Drupal\markdown\Plugin\Markdown\MarkdownParserInterface
   */
  protected $parser;

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'markdown_filter_tips';
  }

  public function buildForm(array $form, FormStateInterface $form_state, bool $long = FALSE, MarkdownParserInterface $parser = NULL) {
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
      $form = $parser->getSummary();
    }
    $form['#type'] = 'container';
    return $form;
  }

  protected function buildGuidelines(array $guides = []) {
    $allowedTags = $this->parser->getAllowedHtml();
    $build = $this->parser->getSummary();

    $build['help']['#markup'] = '<p>' . $this->t('This site renders CommonMark Markdown. While learning all of the Markdown syntax may feel intimidating at first, learning how to use a very small number of the most basic Markdown syntax is very easy. The following sections will provide examples for commonly used Markdown syntax, what HTML output it generates and how it will display on the site.') . '</p>';

    $build['tips'] = ['#type' => 'vertical_tabs'];

    // Iterate over all the items.
    $header = [
      Html::escape($this->t('Markdown')),
      $this->t('HTML Output') . ' / ' . $this->t('Rendered'),
    ];
    foreach ($guides as $guide_id => $guide) {
      // Build the guide.
      $build['guides'][$guide_id] = [
        '#title' => $guide['title'],
        '#type' => 'fieldset',
        '#group' => 'tips',
      ];

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
        $item_tags_not_allowed = array_diff($item_tags, $allowedTags ?? $item_tags);
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
            $rendered = (string) $this->parser->convertToHtml($markdown);
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
        '#header' => [$this->t('HTML Entity'), $this->t('HTML code'), $this->t('Rendered')],
        '#rows' => $rows,
        '#sticky' => FALSE,
      ],
    ];

//    $build['allowed_tags'] = [
//      '#title' => $this->t('Allowed HTML Tags'),
//      '#access' => !!($allowedTags ?? FALSE),
//      '#type' => 'fieldset',
//      '#group' => 'guides',
//      '#weight' => 10,
//      'tags' => $build['allowed_tags'] ?? ['#markup' => implode(', ', $allowedTags ?? [])],
//    ];

    return $build;
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Intentionally left blank. This form doesn't actually submit anything.
  }

}
