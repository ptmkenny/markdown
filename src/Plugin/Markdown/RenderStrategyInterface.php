<?php

namespace Drupal\markdown\Plugin\Markdown;

/**
 * Interface for supporting markdown render strategies.
 */
interface RenderStrategyInterface {

  /**
   * Strategy used to filter the output of parsed markdown.
   *
   * @var string
   */
  const FILTER_OUTPUT = 'filter_output';

  /**
   * Strategy used to escape HTML input prior to parsing markdown.
   *
   * @var string
   */
  const ESCAPE_INPUT = 'escape_input';

  /**
   * The URL for explaining Markdown and XSS; render strategies.
   *
   * @var string
   */
  const MARKDOWN_XSS_URL = 'https://www.drupal.org/docs/8/modules/markdown/markdown-and-xss';

  /**
   * No render strategy.
   *
   * @var string
   */
  const NONE = 'none';

  /**
   * Strategy used to remove HTML input prior to parsing markdown.
   *
   * @var string
   */
  const STRIP_INPUT = 'strip_input';

  /**
   * Retrieves the user provided (custom) allowed HTML.
   *
   * @return string
   */
  public function getAllowedHtml();

  /**
   * Retrieves the allowed HTML plugins relevant to the object.
   *
   * @return string[]
   *   An indexed array of allowed HTML plugins identifiers.
   */
  public function getAllowedHtmlPlugins();

  /**
   * Retrieves the render strategy to use.
   *
   * @return string
   *   The render strategy.
   */
  public function getRenderStrategy();

}
