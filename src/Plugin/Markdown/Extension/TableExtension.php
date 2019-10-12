<?php

namespace Drupal\markdown\Plugin\Markdown\Extension;

use League\CommonMark\EnvironmentAwareInterface;
use League\CommonMark\EnvironmentInterface;
use League\CommonMark\Ext\Table\TableExtension as LeagueTableExtension;

/**
 * Class TableExtension.
 *
 * @MarkdownExtension(
 *   parser = "thephpleague/commonmark",
 *   id = "thephpleague/commonmark-ext-table",
 *   checkClass = "\League\CommonMark\Ext\Table\TableExtension",
 *   composer = "league/commonmark-ext-table",
 *   label = @Translation("Table"),
 *   description = @Translation("Adds the ability to create tables in CommonMark documents."),
 *   homepage = "https://github.com/thephpleague/commonmark-ext-table",
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
