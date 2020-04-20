<?php

namespace Drupal\markdown\Plugin\Markdown\Extension;

use League\CommonMark\EnvironmentAwareInterface;
use League\CommonMark\EnvironmentInterface;
use League\CommonMark\Extension\Table\TableExtension as LeagueTableExtension;

/**
 * Class TableExtension.
 *
 * @MarkdownExtension(
 *   id = "thephpleague/commonmark-ext-table",
 *   installed = "\League\CommonMark\Extension\Table\TableExtension",
 *   label = @Translation("Table"),
 *   description = @Translation("Adds the ability to create tables in CommonMark documents."),
 *   url = "https://github.com/thephpleague/commonmark",
 *   parsers = {"thephpleague/commonmark", "thephpleague/commonmark-gfm"},
 * )
 */
class TableExtension extends CommonMarkExtension implements EnvironmentAwareInterface {

  /**
   * {@inheritdoc}
   */
  public function setEnvironment(EnvironmentInterface $environment) {
    $environment->addExtension(new LeagueTableExtension());
  }

}
