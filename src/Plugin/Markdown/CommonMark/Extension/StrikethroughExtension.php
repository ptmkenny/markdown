<?php

namespace Drupal\markdown\Plugin\Markdown\CommonMark\Extension;

use Drupal\Core\Theme\ActiveTheme;
use Drupal\markdown\Plugin\Markdown\AllowedHtmlInterface;
use Drupal\markdown\Plugin\Markdown\CommonMark\BaseExtension;
use Drupal\markdown\Plugin\Markdown\ParserInterface;
use League\CommonMark\ConfigurableEnvironmentInterface;
use League\CommonMark\Extension\Strikethrough\StrikethroughExtension as LeagueStrikethroughExtension;

/**
 * Strikethrough extension.
 *
 * @MarkdownExtension(
 *   id = "league/commonmark-ext-strikethrough",
 *   label = @Translation("Strikethrough"),
 *   installed = "\League\CommonMark\Extension\Strikethrough\StrikethroughExtension",
 *   description = @Translation("Adds support for GFM-style strikethrough syntax. It allows users to use <code>~~</code> in order to indicate text that should be rendered within <code>&lt;del&gt;</code> tags."),
 *   url = "https://commonmark.thephpleague.com/extensions/strikethrough/",
 * )
 * @MarkdownAllowedHtml(
 *   id = "league/commonmark-ext-strikethrough",
 *   label = @Translation("Strikethrough"),
 *   installed = "\League\CommonMark\Extension\Strikethrough\StrikethroughExtension",
 *   url = "https://commonmark.thephpleague.com/extensions/strikethrough/",
 * )
 */
class StrikethroughExtension extends BaseExtension implements AllowedHtmlInterface {

  /**
   * {@inheritdoc}
   */
  public function allowedHtmlTags(ParserInterface $parser, ActiveTheme $activeTheme = NULL) {
    return [
      'del' => [],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function register(ConfigurableEnvironmentInterface $environment) {
    $environment->addExtension(new LeagueStrikethroughExtension());
  }

}
