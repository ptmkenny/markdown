<?php

namespace Drupal\markdown\Exception;

/**
 * Exception thrown when a URL is expected to exist but does not.
 */
class MarkdownUrlNotExistsException extends MarkdownException {

  /**
   * {@inheritdoc}
   */
  public function __construct($file, $code = 0, $previous = NULL) {
    parent::__construct(sprintf('Markdown cannot parse the URL: %s', $file), $code, $previous);
  }

}
