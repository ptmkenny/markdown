<?php

namespace Drupal\markdown\Plugin\Markdown\Extension;

use League\CommonMark\EnvironmentAwareInterface;
use League\CommonMark\EnvironmentInterface;
use League\CommonMark\Extension\Table\TableExtension as LeagueTableExtension;

/**
 * @MarkdownExtension(
 *   id = "league/commonmark-ext-table",
 *   installed = "\League\CommonMark\Extension\Table\TableExtension",
 *   label = @Translation("Table"),
 *   description = @Translation("Adds the ability to create tables in CommonMark documents."),
 *   url = "https://commonmark.thephpleague.com/extensions/tables/",
 * )
 */
class TableExtension extends CommonMarkExtensionBase implements EnvironmentAwareInterface {

  /**
   * {@inheritdoc}
   */
  public function setEnvironment(EnvironmentInterface $environment) {
    $environment->addExtension(new LeagueTableExtension());
  }

}
