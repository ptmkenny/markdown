<?php

namespace Drupal\markdown;

use Drupal\Component\Render\MarkupInterface;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Language\LanguageInterface;

interface ParsedMarkdownInterface extends MarkupInterface, \Countable, \Serializable {

  /**
   * Default list of allowed HTML.
   *
   * @var string
   *
   * @see \Drupal\filter\Plugin\Filter\FilterHtml::process()
   */
  const ALLOWED_HTML = '<a href hreflang> <abbr> <acronym> <address> <article> <aside> <b> <bdi> <bdo> <big> <blockquote cite> <br> <caption> <cite> <code>' .
    '<col> <colgroup> <command> <dd> <del> <details> <dfn> <div> <dl> <dt> <em> <figcaption> <figure> <footer> <g> <h2 id=\'jump-*\'> <h3 id> <h4 id> <h5 id> <h6 id>' .
    '<header> <hgroup> <hr> <i> <img> <ins> <kbd> <li> <mark> <menu> <meter> <nav> <ol start type=\'1 A I\'> <output> <p> <path d fill*> <pre> <progress> <q> <rp> <rt> <ruby> <s>' .
    '<samp> <section> <small> <span> <strong> <sub> <summary> <sup> <svg viewBox> <table> <tbody> <td> <tfoot> <th> <thead> <time> <tr> <tt> <u> <ul type> <var> <wbr>';

  /**
   * Indicates the item should never be removed unless explicitly deleted.
   */
  const PERMANENT = CacheBackendInterface::CACHE_PERMANENT;

  /**
   * Creates new ParsedMarkdown object.
   *
   * @param string $markdown
   *   The raw markdown.
   * @param string $html
   *   The parsed HTML from $markdown.
   * @param \Drupal\Core\Language\LanguageInterface|null $language
   *   The language of the parsed markdown, if known.
   *
   * @return static
   */
  public static function create($markdown = '', $html = '', LanguageInterface $language = NULL);

  /**
   * Normalizes markdown.
   *
   * @param string $markdown
   *   The markdown to normalize.
   *
   * @return string
   *   The normalized markdown.
   */
  public static function normalizeMarkdown($markdown);

  /**
   * Retrieves the UNIX timestamp for when this object should expire.
   *
   * Note: this method should handle the use case of a string being set to
   * indicate a relative future time.
   *
   * @param int $from_time
   *   A UNIX timestamp used to expire from. This will only be used when the
   *   expire value has been set to a relative time in the future, e.g. day,
   *   week, month, etc. If not set, this current request time will be used.
   *
   * @return int
   *   The UNIX timestamp.
   */
  public function getExpire($from_time = NULL);

  /**
   * Retrieves the parsed HTML.
   *
   * @return string
   *   The parsed HTML.
   */
  public function getHtml();

  /**
   * Retrieves the identifier for this object.
   *
   * Note: if no identifier is currently set, a unique hash based on the
   * contents of the parsed HTML will be used.
   *
   * @return string
   *   The identifier for this object.
   */
  public function getId();

  /**
   * Retrieves the human-readable label for this object.
   *
   * Note: if no label is currently set, the identifier for the object is
   * returned instead.
   *
   * @return string
   *   The label for this object.
   */
  public function getLabel();

  /**
   * Retrieves the raw markdown source.
   *
   * @return string
   *   The markdown source.
   */
  public function getMarkdown();

  /**
   * Retrieves the file size of the parsed HTML.
   *
   * @param bool $formatted
   *   Flag indicating whether to retrieve the formatted, human-readable,
   *   file size.
   * @param int $decimals
   *   The number of decimal points to use if $formatted is TRUE.
   *
   * @return int|string
   *   The raw file size in bytes or the formatted human-readable file size.
   */
  public function getSize($formatted = FALSE, $decimals = 2);

  /**
   * Compares whether the provided markdown matches this object.
   *
   * @param string|\Drupal\markdown\ParsedMarkdownInterface $markdown
   *   An external markdown source.
   *
   * @return bool
   *   TRUE or FALSE
   */
  public function matches($markdown);

  /**
   * Sets the allowed HTML.
   *
   * @param string|true $allowed_html
   *   Optional. HTML that is allowed in the parsed markdown to ensure it is
   *   safe from XSS vulnerabilities. If TRUE is passed, the HTML parsed from
   *   markdown will be returned as is.
   *
   * @return static
   */
  public function setAllowedHtml($allowed_html = self::ALLOWED_HTML);

  /**
   * Sets the object's expiration timestamp.
   *
   * @param int|string $expire
   *   A UNIX timestamp or a string indicating a relative time in the future of
   *   when this object is to expire, e.g. "1+ day".
   *
   * @return static
   */
  public function setExpire($expire = Cache::PERMANENT);

  /**
   * Sets the object's identifier.
   *
   * @param string $id
   *   An identifier.
   *
   * @return static
   */
  public function setId($id);

  /**
   * Sets the object's label.
   *
   * @param string $label
   *   A human-readable label.
   *
   * @return static
   */
  public function setLabel($label);

}
