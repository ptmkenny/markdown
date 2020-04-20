<?php

namespace Drupal\markdown\Plugin\Markdown;

/**
 * @MarkdownParser(
 *   id = "erusev/parsedown-extra",
 *   label = @Translation("Parsedown Extra"),
 *   url = "https://github.com/erusev/parsedown-extra",
 * )
 */
class ParsedownExtra extends Parsedown {

  /**
   * {@inheritdoc}
   */
  protected static $parserClass = '\\ParsedownExtra';

}
