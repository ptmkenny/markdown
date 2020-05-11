<?php

namespace Drupal\markdown\Plugin\Markdown\CommonMark;

use League\CommonMark\Environment;

/**
 * Support for CommonMark GFM by The League of Extraordinary Packages.
 *
 * @MarkdownParser(
 *   id = "league/commonmark-gfm",
 *   label = @Translation("CommonMark GFM"),
 *   description = @Translation("A robust, highly-extensible Markdown parser for PHP based on the Github-Flavored Markdown specification."),
 *   installed = "\League\CommonMark\GithubFlavoredMarkdownConverter",
 *   version = "\League\CommonMark\GithubFlavoredMarkdownConverter::VERSION",
 *   versionConstraint = "^1.3 || ^2.0",
 *   url = "https://commonmark.thephpleague.com",
 *   extensionInterfaces = {
 *     "\Drupal\markdown\Plugin\Markdown\CommonMark\ExtensionInterface",
 *   },
 *   bundledExtensions = {
 *     "league/commonmark-ext-disallowed-raw-html",
 *     "league/commonmark-ext-strikethrough",
 *     "league/commonmark-ext-table",
 *     "league/commonmark-ext-task-list",
 *   },
 * )
 */
class CommonMarkGfm extends CommonMark {

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
