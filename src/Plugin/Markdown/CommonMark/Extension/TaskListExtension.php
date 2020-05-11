<?php

namespace Drupal\markdown\Plugin\Markdown\CommonMark\Extension;

use Drupal\Core\Theme\ActiveTheme;
use Drupal\markdown\Plugin\Markdown\AllowedHtmlInterface;
use Drupal\markdown\Plugin\Markdown\CommonMark\BaseExtension;
use Drupal\markdown\Plugin\Markdown\ParserInterface;
use League\CommonMark\EnvironmentAwareInterface;
use League\CommonMark\EnvironmentInterface;
use League\CommonMark\Extension\TaskList\TaskListExtension as LeagueTaskListExtension;

/**
 * Task List extension.
 *
 * @MarkdownExtension(
 *   id = "league/commonmark-ext-task-list",
 *   label = @Translation("Task List"),
 *   installed = "\League\CommonMark\Extension\TaskList\TaskListExtension",
 *   description = @Translation("Adds support for GFM-style task lists."),
 *   url = "https://commonmark.thephpleague.com/extensions/task-lists/",
 * )
 * @MarkdownAllowedHtml(
 *   id = "league/commonmark-ext-task-list",
 *   label = @Translation("Task List"),
 *   installed = "\League\CommonMark\Extension\TaskList\TaskListExtension",
 * )
 */
class TaskListExtension extends BaseExtension implements AllowedHtmlInterface, EnvironmentAwareInterface {

  /**
   * {@inheritdoc}
   */
  public function allowedHtmlTags(ParserInterface $parser, ActiveTheme $activeTheme = NULL) {
    return [
      'input' => [
        'checked' => TRUE,
        'disabled' => TRUE,
        'type' => 'checkbox',
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function setEnvironment(EnvironmentInterface $environment) {
    $environment->addExtension(new LeagueTaskListExtension());
  }

}
