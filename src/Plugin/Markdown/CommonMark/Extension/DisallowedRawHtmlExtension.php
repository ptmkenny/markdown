<?php

namespace Drupal\markdown\Plugin\Markdown\CommonMark\Extension;

use Drupal\markdown\Plugin\Markdown\CommonMark\BaseExtension;
use League\CommonMark\EnvironmentAwareInterface;
use League\CommonMark\EnvironmentInterface;
use League\CommonMark\Extension\DisallowedRawHTML\DisallowedRawHTMLExtension as LeagueDisallowedRawHTMLExtension;

/**
 * Disallowed Raw HTML extension.
 *
 * @MarkdownExtension(
 *   id = "league/commonmark-ext-disallowed-raw-html",
 *   label = @Translation("Disallowed Raw HTML"),
 *   installed = "\League\CommonMark\Extension\DisallowedRawHTML\DisallowedRawHTMLExtension",
 *   description = @Translation("Automatically filters certain HTML tags when rendering output."),
 *   url = "https://commonmark.thephpleague.com/extensions/disallowed-raw-html/",
 * )
 */
class DisallowedRawHtmlExtension extends BaseExtension implements EnvironmentAwareInterface {

  /**
   * {@inheritdoc}
   */
  public function setEnvironment(EnvironmentInterface $environment) {
    $environment->addExtension(new LeagueDisallowedRawHTMLExtension());
  }

}
