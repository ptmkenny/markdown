<?php

namespace Drupal\markdown\Twig;

use Drupal\markdown\MarkdownInterface;
use Twig\Token;

/**
 * Twig Markdown Token Parser.
 */
class TokenParser extends \Twig_TokenParser {

  /**
   * An instance of a markdown processor to use.
   *
   * @var \Drupal\markdown\MarkdownInterface
   */
  protected $markdown;

  /**
   * {@inheritdoc}
   */
  public function __construct(MarkdownInterface $markdown) {
    $this->markdown = $markdown;
  }

  /**
   * {@inheritdoc}
   */
  public function parse(Token $token) {
    $tag = $this->getTag();
    $line = $token->getLine();
    $this->parser->getStream()->expect(\Twig_Token::BLOCK_END_TYPE);
    $body = $this->parser->subparse(function (\Twig_Token $token) use ($tag) {
      return $token->test("end$tag");
    }, TRUE);
    $this->parser->getStream()->expect(\Twig_Token::BLOCK_END_TYPE);
    return new Node($body, $line, $tag);
  }

  /**
   * {@inheritdoc}
   */
  public function getTag() {
    return 'markdown';
  }

  /**
   * Return the markdown instance being used.
   *
   * @return \Drupal\markdown\MarkdownInterface
   *   The Markdown instance.
   */
  public function getMarkdown() {
    return $this->markdown;
  }

}
