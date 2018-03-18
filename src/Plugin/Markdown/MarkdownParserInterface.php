<?php

namespace Drupal\markdown\Plugin\Markdown;

use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\markdown\MarkdownInterface;
use Drupal\markdown\Plugin\Filter\MarkdownFilterInterface;

/**
 * Interface MarkdownInterface.
 */
interface MarkdownParserInterface extends MarkdownInterface, PluginInspectionInterface {

  /**
   * Builds a guide on how to use the Markdown Parser.
   *
   * @param \Drupal\markdown\Plugin\Filter\MarkdownFilterInterface $filter
   *   The Markdown filter this guide is building for.
   *
   * @return array
   *   A render array.
   */
  public function buildGuide(MarkdownFilterInterface $filter);

  /**
   * Retrieves MarkdownExtension plugins.
   *
   * @param \Drupal\markdown\Plugin\Filter\MarkdownFilterInterface $filter
   *   A specific filter where settings should be used to configure extensions.
   *
   * @return \Drupal\markdown\Plugin\Markdown\Extension\MarkdownExtensionInterface[]
   *   An array of MarkdownExtension plugins.
   */
  public function getExtensions(MarkdownFilterInterface $filter = NULL);

  /**
   * Retrieves a filter format entity.
   *
   * @param string $format
   *   A filter format identifier or entity instance.
   *
   * @return \Drupal\filter\FilterFormatInterface|object
   *   A filter format entity.
   */
  public function getFilterFormat($format = NULL);

  /**
   * Retrieves a short summary of what the MarkdownParser does.
   *
   * @param \Drupal\markdown\Plugin\Filter\MarkdownFilterInterface $filter
   *   The Markdown filter that is displaying this summary.
   *
   * @return array
   *   A render array.
   */
  public function getSummary(MarkdownFilterInterface $filter);

  /**
   * The current version of the parser.
   *
   * @return string
   *   The version.
   */
  public function getVersion();

  /**
   * Indicates whether the parser is available.
   *
   * @return bool
   *   TRUE or FALSE
   */
  public function isAvailable();

  /**
   * Displays the human-readable label of the MarkdownParser plugin.
   *
   * @param bool $show_version
   *   Flag indicating whether to show the version with the label.
   *
   * @return string
   *   The label.
   */
  public function label($show_version = TRUE);

  /**
   * Generates a filter's tip.
   *
   * A filter's tips should be informative and to the point. Short tips are
   * preferably one-liners.
   *
   * @param \Drupal\markdown\Plugin\Filter\MarkdownFilterInterface $filter
   *   The Markdown filter that is displaying the tips.
   * @param bool $long
   *   Whether this callback should return a short tip to display in a form
   *   (FALSE), or whether a more elaborate filter tips should be returned for
   *   template_preprocess_filter_tips() (TRUE).
   *
   * @return string|null
   *   Translated text to display as a tip, or NULL if this filter has no tip.
   */
  public function tips(MarkdownFilterInterface $filter, $long = FALSE);

}
