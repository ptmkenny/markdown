<?php

namespace Drupal\markdown\Plugin\Markdown\CommonMark\Extension;

use Drupal\markdown\Plugin\Markdown\CommonMark\BaseExtension;
use League\CommonMark\EnvironmentAwareInterface;
use League\CommonMark\EnvironmentInterface;
use League\CommonMark\Extension\Autolink\AutolinkExtension as LeagueAutolinkExtension;

/**
 * Autolink extension.
 *
 * @MarkdownExtension(
 *   id = "league/commonmark-ext-autolink",
 *   label = @Translation("Autolink"),
 *   installed = "\League\CommonMark\Extension\Autolink\AutolinkExtension",
 *   description = @Translation("Automatically links URLs and email addresses even when the CommonMark <code>&lt;...&gt;</code> autolink syntax is not used."),
 *   url = "https://commonmark.thephpleague.com/extensions/autolinks/",
 * )
 */
class AutolinkExtension extends BaseExtension implements EnvironmentAwareInterface {

  /**
   * {@inheritdoc}
   */
  public function setEnvironment(EnvironmentInterface $environment) {
    $environment->addExtension(new LeagueAutolinkExtension());
  }

}
