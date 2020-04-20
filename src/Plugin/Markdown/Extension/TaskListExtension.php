<?php

namespace Drupal\markdown\Plugin\Markdown\Extension;

use League\CommonMark\EnvironmentAwareInterface;
use League\CommonMark\EnvironmentInterface;
use League\CommonMark\Extension\TaskList\TaskListExtension as LeagueTaskListExtension;

/**
 * Class TableExtension.
 *
 * @MarkdownExtension(
 *   id = "thephpleague/commonmark-ext-task-list",
 *   label = @Translation("Task List"),
 *   installed = "\League\CommonMark\Extension\TaskList\TaskListExtension",
 *   description = @Translation("Adds GFM-style task list items to the league/commonmark Markdown parser for PHP."),
 *   url = "https://github.com/thephpleague/commonmark",
 *   parsers = {"thephpleague/commonmark", "thephpleague/commonmark-gfm"},
 * )
 */
class TaskListExtension extends CommonMarkExtension implements EnvironmentAwareInterface {

  /**
   * {@inheritdoc}
   */
  public function setEnvironment(EnvironmentInterface $environment) {
    $environment->addExtension(new LeagueTaskListExtension());
  }

}
