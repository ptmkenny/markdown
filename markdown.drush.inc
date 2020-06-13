<?php

/**
 * @file
 * Drush commands for Drupal Markdown.
 */

use Drupal\markdown\Commands\MarkdownCommands;

/**
 * @defgroup markdown_drush Drupal Markdown Drush Integration
 *
 * @deprecated in markdown:8.x-2.0 and is removed from markdown:3.0.0.
 *   No replacement.
 * @see https://www.drupal.org/project/markdown/issues/3103679
 *
 * @{
 * Drush command integration for Drupal Markdown.
 */

/**
 * Implements hook_drush_help().
 */
function markdown_drush_help($command) {
  switch ($command) {
    case 'drush:markdown-version-hash':
      return dt('Generates the version hashes necessary to detect composer library versions.');
  }
  return FALSE;
}

/**
 * Retrieves the class used to proxy these legacy Drush commands into.
 *
 * @return \Drupal\markdown\Commands\MarkdownCommands
 */
function _drush_markdown_commands() {
  static $markdownCommands;
  if (!isset($markdownCommands)) {
    $markdownCommands = MarkdownCommands::create();
  }
  return $markdownCommands;
}

/**
 * Implements hook_drush_command().
 */
function markdown_drush_command() {
  $items = [];
  $items['markdown-version-hash'] = [
    'description' => dt('Generates the version hashes necessary to detect composer library versions.'),
    'arguments' => [
      'package' => 'A specific composer vendor/name package.',
    ],
    'options' => [
      'force' => 'Comma delimited list of spreads (e.g. mayonnaise, mustard)',
    ],
    'examples' => [
      'Standard example' => 'drush markdown-version-hash',
    ],
    'aliases' => ['mvh', 'markdown:version-hash'],
  ];
  return $items;
}

/**
 * Callback function for drush markdown-version-hash.
 */
function drush_markdown_version_hash($package = NULL) {
  $options = array_intersect_key(drush_get_merged_options(), drush_get_command()['options']);
  return _drush_markdown_commands()->versionHash($package, $options);
}

/**
 * @} End of "defgroup markdown_drush".
 */
