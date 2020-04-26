<?php

namespace Drupal\markdown;

use Drupal\Component\Utility\Crypt;
use Drupal\Component\Utility\Unicode;
use Drupal\Component\Utility\Xss;
use Drupal\Core\Language\LanguageInterface;

class ParsedMarkdown implements ParsedMarkdownInterface {

  /**
   * An array of allowed HTML tags that are permitted in the parsed HTML.
   *
   * This is to ensure it is safe from XSS vulnerabilities.
   * If TRUE, all tags are allowed.
   *
   * @var array|true
   */
  protected $allowedTags = self::ALLOWED_TAGS;

  /**
   * A UNIX timestamp of when this object is to expire.
   *
   * @var int
   */
  protected $expire = ParsedMarkdownInterface::PERMANENT;

  /**
   * The parsed HTML.
   *
   * @var string
   */
  protected $html;

  /**
   * A unique identifier.
   *
   * @var string
   */
  protected $id;

  /**
   * A human-readable label.
   *
   * @var string
   */
  protected $label;

  /**
   * The raw markdown.
   *
   * @var string
   */
  protected $markdown;

  /**
   * The language of the parsed markdown, if known.
   *
   * @var \Drupal\Core\Language\LanguageInterface|null
   */
  protected $language;

  /**
   * The byte size of the rendered HTML.
   *
   * @var int
   */
  protected $size;

  /**
   * ParsedMarkdown constructor.
   *
   * @param string $markdown
   *   The raw markdown.
   * @param string $html
   *   The parsed HTML from $markdown.
   * @param \Drupal\Core\Language\LanguageInterface|null $language
   *   Optional. The language of the parsed markdown, if known.
   */
  public function __construct($markdown = '', $html = '', LanguageInterface $language = NULL) {
    $this->html = trim($html);
    $this->markdown = trim($markdown);
    $this->language = $language;
  }

  /**
   * {@inheritdoc}
   */
  public function __toString() {
    return $this->getHtml();
  }

  /**
   * {@inheritdoc}
   */
  public static function create($markdown = '', $html = '', LanguageInterface $language = NULL) {
    return new static($markdown, $html, $language);
  }

  /**
   * {@inheritdoc}
   */
  public function count() {
    return $this->getSize();
  }

  /**
   * {@inheritdoc}
   */
  public function getExpire($from_time = NULL) {
    $expire = $this->expire;

    // Handle relative time.
    if (is_string($expire)) {
      $expire = strtotime($expire, $from_time ?: \Drupal::time()
        ->getRequestTime());
    }

    return $expire;
  }

  /**
   * {@inheritdoc}
   */
  public function getHtml() {
    if ($this->allowedTags === TRUE) {
      return $this->html;
    }
    return Xss::filter($this->html, $this->allowedTags);
  }

  /**
   * {@inheritdoc}
   */
  public function getId() {
    if ($this->id === NULL) {
      $this->id = Crypt::hashBase64($this->getHtml());
    }
    return $this->id;
  }

  /**
   * {@inheritdoc}
   */
  public function getLabel() {
    return $this->label ?: $this->getId();
  }

  /**
   * {@inheritdoc}
   */
  public function getMarkdown() {
    return static::normalizeMarkdown($this->markdown);
  }

  /**
   * {@inheritdoc}
   */
  public function getSize($formatted = FALSE, $decimals = 2) {
    if ($this->size === NULL) {
      $this->size = Unicode::strlen($this->getHtml());
    }
    return $formatted ? number_format($this->size, $decimals) : $this->size;
  }

  /**
   * {@inheritdoc}
   */
  public function jsonSerialize() {
    return $this->__toString();
  }

  /**
   * {@inheritdoc}
   */
  public function matches($markdown) {
    if ($markdown instanceof static) {
      return $markdown->getMarkdown() === $this->getMarkdown();
    }
    return static::normalizeMarkdown($markdown) === $this->getMarkdown();
  }

  /**
   * {@inheritdoc}
   */
  public static function normalizeMarkdown($markdown) {
    return $markdown === '' ? '' : preg_replace('/\\r\\n|\\n/', "\n", (string) $markdown);
  }

  /**
   * {@inheritdoc}
   */
  public function serialize() {
    $data['object'] = serialize(get_object_vars($this));

    // Determine if PHP has gzip capabilities.
    $data['gzip'] = extension_loaded('zlib');

    // Compress and encode the markdown and html output.
    if ($data['gzip']) {
      $data['object'] = base64_encode(gzencode($data['object'], 9));
    }

    return serialize($data);
  }

  /**
   * {@inheritdoc}
   */
  public function setAllowedTags($allowed_tags = self::ALLOWED_TAGS) {
    if ($allowed_tags !== TRUE && !is_array($allowed_tags)) {
      $allowed_tags = static::ALLOWED_TAGS;
    }
    $this->allowedTags = $allowed_tags;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setExpire($expire = ParsedMarkdownInterface::PERMANENT) {
    $this->expire = $expire;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setId($id) {
    $this->id = $id;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setLabel($label) {
    $this->label = $label;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function unserialize($serialized) {
    $data = unserialize($serialized);

    // Data was gzipped.
    if ($data['gzip']) {
      // Decompress data if PHP has gzip capabilities.
      if (extension_loaded('zlib')) {
        $data['object'] = gzdecode(base64_decode($data['object']));
      }
      else {
        $this->markdown = sprintf('This cached %s object was stored using gzip compression. Unable to decompress. The PHP on this server must have the "zlib" extension installed.', static::class);
        $this->html = $this->markdown;
        return;
      }
    }

    $object = unserialize($data['object']);
    foreach ($object as $prop => $value) {
      $this->$prop = $value;
    }
  }

}
