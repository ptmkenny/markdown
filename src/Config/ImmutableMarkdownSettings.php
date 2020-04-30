<?php

namespace Drupal\markdown\Config;

use Drupal\markdown\MarkdownSettingsInterface;

class ImmutableMarkdownSettings extends ImmutableMarkdownConfig implements MarkdownSettingsInterface {

  use MarkdownSettingsTrait;

}
