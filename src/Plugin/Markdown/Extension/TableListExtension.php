<?php

namespace Drupal\markdown\Plugin\Markdown\Extension;

use League\CommonMark\EnvironmentAwareInterface;
use League\CommonMark\EnvironmentInterface;
use League\CommonMark\Ext\TaskList\TaskListExtension as LeagueTaskListExtension;

/**
 * Class TableExtension.
 *
 * @MarkdownExtension(
 *   parser = "thephpleague/commonmark",
 *   id = "thephpleague/commonmark-ext-task-list",
 *   checkClass = "\League\CommonMark\Ext\TaskList\TaskListExtension",
 *   composer = "league/commonmark-ext-task-list",
 *   label = @Translation("Table - Task Lists"),
 *   description = @Translation("Adds GFM-style task list items to the league/commonmark Markdown parser for PHP."),
 *   homepage = "https://github.com/thephpleague/commonmark-ext-task-list",
 * )
 */
class TableListExtension extends CommonMarkExtension implements EnvironmentAwareInterface {

  /**
   * {@inheritdoc}
   */
  public function setEnvironment(EnvironmentInterface $environment) {
    $environment->addExtension(new LeagueTaskListExtension());
  }

}
