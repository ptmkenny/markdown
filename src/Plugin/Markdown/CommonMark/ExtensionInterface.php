<?php

namespace Drupal\markdown\Plugin\Markdown\CommonMark;

use Drupal\markdown\Plugin\Markdown\ExtensionInterface as MarkdownExtensionInterface;
use League\CommonMark\Extension\ExtensionInterface as LeagueExtensionInterface;

/**
 * Interface for CommonMark Extensions.
 */
interface ExtensionInterface extends MarkdownExtensionInterface, LeagueExtensionInterface {
}
