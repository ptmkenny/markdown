<?php

namespace Drupal\markdown\Plugin\Markdown\CommonMark\Extension;

use League\CommonMark\Inline\Element\Link;
use League\CommonMark\Inline\Parser\InlineParserInterface;
use League\CommonMark\InlineParserContext;

/**
 * Custom implementation of CommonMark's InlineMentionParser.
 */
class InlineMentionParser implements InlineParserInterface {

  /**
   * The link pattern.
   *
   * @var string|callable
   */
  protected $linkPattern;

  /**
   * The handle regular expression.
   *
   * @var string
   */
  protected $handleRegex;

  /**
   * The symbol to search for.
   *
   * @var string
   */
  protected $symbol;

  /**
   * Inline mention parser.
   *
   * @param string $symbol
   *   The symbol to search for (i.e. @).
   * @param string|callable $linkPattern
   *   The link pattern.
   * @param string $handleRegex
   *   The handle regular expression.
   */
  public function __construct($symbol, $linkPattern, $handleRegex = '/^[A-Za-z0-9_]+(?!\w)/') {
    $this->symbol = $symbol;
    $this->linkPattern = $linkPattern;
    $this->handleRegex = $handleRegex;
  }

  /**
   * Creates a new inline mention parser.
   *
   * @param string $symbol
   *   The symbol to search for (i.e. @).
   * @param string|callable $linkPattern
   *   The link pattern.
   * @param string $handleRegex
   *   The handle regular expression.
   *
   * @return static
   */
  public static function create($symbol, $linkPattern, $handleRegex = '/^[A-Za-z0-9_]+(?!\w)/') {
    return new static($symbol, $linkPattern, $handleRegex);
  }

  /**
   * {@inheritdoc}
   *
   * @noinspection PhpLanguageLevelInspection
   * @noinspection PhpInappropriateInheritDocUsageInspection
   */
  public function getCharacters(): array {
    return [$this->symbol];
  }

  /**
   * {@inheritdoc}
   *
   * @noinspection PhpLanguageLevelInspection
   * @noinspection PhpInappropriateInheritDocUsageInspection
   */
  public function parse(InlineParserContext $inlineContext): bool {
    $cursor = $inlineContext->getCursor();

    // The handle symbol must not have any other characters immediately prior.
    $previousChar = $cursor->peek(-1);
    if ($previousChar !== NULL && $previousChar !== ' ') {
      // No need to restore state first as peek() doesn't modify the cursor.
      return FALSE;
    }

    // Save the cursor state in case we need to rewind and bail.
    $previousState = $cursor->saveState();

    // Advance past the handle symbol to keep parsing simpler.
    $cursor->advance();

    // Parse the handle.
    $handle = $cursor->match($this->handleRegex);
    if (empty($handle)) {
      // Regex failed to match; this isn't a valid handle.
      $cursor->restoreState($previousState);

      return FALSE;
    }

    $symbol = $this->symbol;
    $label = $symbol . $handle;
    if (is_callable($this->linkPattern)) {
      try {
        $args = [&$handle, &$label, $symbol];
        $url = call_user_func_array($this->linkPattern, $args);
      }
      catch (\Exception $exception) {
        // Intentionally do nothing.
      }
      if (empty($url)) {
        // URL failed to be built, this isn't a valid handle.
        $cursor->restoreState($previousState);
        return FALSE;
      }
    }
    else {
      $url = \sprintf($this->linkPattern, $handle);
    }

    $inlineContext->getContainer()->appendChild(new Link($url, $label));

    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function getName() {
    return 'inline-mention';
  }

}
