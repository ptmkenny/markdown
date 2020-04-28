<?php

namespace Drupal\markdown\Plugin\Markdown;

/**
 * @MarkdownParser(
 *   id = "erusev/parsedown-extra",
 *   label = @Translation("Parsedown Extra"),
 *   url = "https://github.com/erusev/parsedown-extra",
 *   weight = 20,
 * )
 */
class ParsedownExtra extends Parsedown {

  /**
   * {@inheritdoc}
   */
  protected static $parsedownClass = '\\ParsedownExtra';

}
