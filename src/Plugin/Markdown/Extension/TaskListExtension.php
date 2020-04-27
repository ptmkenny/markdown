<?php

namespace Drupal\markdown\Plugin\Markdown\Extension;

use League\CommonMark\EnvironmentAwareInterface;
use League\CommonMark\EnvironmentInterface;
use League\CommonMark\Extension\TaskList\TaskListExtension as LeagueTaskListExtension;

/**
 * @MarkdownExtension(
 *   id = "league/commonmark-ext-task-list",
 *   label = @Translation("Task List"),
 *   installed = "\League\CommonMark\Extension\TaskList\TaskListExtension",
 *   description = @Translation("Adds support for GFM-style task list items."),
 *   url = "https://github.com/thephpleague/commonmark",
 *   parsers = {"league/commonmark", "league/commonmark-gfm"},
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
