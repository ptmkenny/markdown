<?php

namespace Drupal\markdown\Plugin\Markdown;

use League\CommonMark\Environment;

/**
 * @MarkdownParser(
 *   id = "thephpleague/commonmark-gfm",
 *   label = @Translation("CommonMark (GFM)"),
 *   url = "https://commonmark.thephpleague.com",
 * )
 */
class LeagueCommonMarkGfm extends LeagueCommonMark {

  /**
   * {@inheritdoc}
   */
  protected static $converterClass = '\\League\\CommonMark\\GithubFlavoredMarkdownConverter';

  /**
   * {@inheritdoc}
   */
  protected function createEnvironment() {
    return Environment::createGFMEnvironment();
  }

}
