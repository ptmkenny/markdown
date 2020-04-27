<?php

namespace Drupal\markdown\Plugin\Markdown;

use League\CommonMark\Environment;

/**
 * @MarkdownParser(
 *   id = "league/commonmark-gfm",
 *   label = @Translation("CommonMark GFM"),
 *   description = @Translation("A robust, highly-extensible Markdown parser for PHP based on the Github-Flavored Markdown specification."),
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
