<?php

namespace Drupal\markdown\Exception;

/**
 * Exception thrown when a file is expected to exist but does not.
 */
class MarkdownFileNotExistsException extends MarkdownException {

  /**
   * {@inheritdoc}
   */
  public function __construct($file, $code = 0, $previous = NULL) {
    parent::__construct(sprintf('Markdown cannot parse the file: %s', $file), $code, $previous);
  }

}
