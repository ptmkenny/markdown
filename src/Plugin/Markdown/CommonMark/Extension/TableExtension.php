<?php

namespace Drupal\markdown\Plugin\Markdown\CommonMark\Extension;

use Drupal\Core\Theme\ActiveTheme;
use Drupal\markdown\Plugin\Markdown\AllowedHtmlInterface;
use Drupal\markdown\Plugin\Markdown\CommonMark\BaseExtension;
use Drupal\markdown\Plugin\Markdown\ParserInterface;
use League\CommonMark\ConfigurableEnvironmentInterface;
use League\CommonMark\Extension\Table\TableExtension as LeagueTableExtension;

/**
 * Table extension.
 *
 * @MarkdownExtension(
 *   id = "league/commonmark-ext-table",
 *   label = @Translation("Table"),
 *   installed = "\League\CommonMark\Extension\Table\TableExtension",
 *   description = @Translation("Adds the ability to create tables in CommonMark documents."),
 *   url = "https://commonmark.thephpleague.com/extensions/tables/",
 * )
 * @MarkdownAllowedHtml(
 *   id = "league/commonmark-ext-table",
 *   label = @Translation("Table"),
 *   installed = "\League\CommonMark\Extension\Table\TableExtension",
 *   url = "https://commonmark.thephpleague.com/extensions/tables/",
 * )
 */
class TableExtension extends BaseExtension implements AllowedHtmlInterface {

  /**
   * {@inheritdoc}
   */
  public function allowedHtmlTags(ParserInterface $parser, ActiveTheme $activeTheme = NULL) {
    return [
      'caption' => [],
      'col' => [
        'span' => TRUE,
      ],
      'colgroup' => [
        'span' => TRUE,
      ],
      'table' => [],
      'tbody' => [],
      'td' => [
        'colspan' => TRUE,
        'headers' => TRUE,
        'rowspan' => TRUE,
      ],
      'tfoot' => [],
      'th' => [
        'abbr' => TRUE,
        'colspan' => TRUE,
        'headers' => TRUE,
        'rowspan' => TRUE,
        'scope' => TRUE,
      ],
      'thead' => [],
      'tr' => [],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function register(ConfigurableEnvironmentInterface $environment) {
    $environment->addExtension(new LeagueTableExtension());
  }

}
