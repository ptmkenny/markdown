<?php

namespace Drupal\markdown;

use Drupal\Component\Utility\Crypt;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\DependencyInjection\DependencySerializationTrait;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Drupal\markdown\Exception\MarkdownFileNotExistsException;
use Drupal\markdown\Exception\MarkdownUrlNotExistsException;
use GuzzleHttp\ClientInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

class Markdown implements MarkdownInterface {

  use ContainerAwareTrait;
  use DependencySerializationTrait;
  use StringTranslationTrait;

  /**
   * The cache backend.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cache;

  /**
   * The File System service.
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  protected $fileSystem;

  /**
   * The HTTP Client service.
   *
   * @var \GuzzleHttp\ClientInterface
   */
  protected $httpClient;

  /**
   * A Markdown parser instance.
   *
   * @var \Drupal\markdown\Plugin\Markdown\MarkdownParserInterface
   */
  protected $parser;

  /**
   * The MarkdownParser Plugin Manager.
   *
   * @var \Drupal\markdown\MarkdownParserPluginManagerInterface
   */
  protected $parserManager;

  /**
   * Markdown constructor.
   *
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache
   *   The cache backend.
   * @param \Drupal\Core\File\FileSystemInterface $fileSystem
   *   The File System service.
   * @param \GuzzleHttp\ClientInterface $httpClient
   *   The HTTP Client service.
   * @param \Drupal\markdown\MarkdownParserPluginManagerInterface $parserManager
   *   The Markdown Parser Plugin Manager service.
   */
  public function __construct(CacheBackendInterface $cache, FileSystemInterface $fileSystem, ClientInterface $httpClient, MarkdownParserPluginManagerInterface $parserManager) {
    $this->cache = $cache;
    $this->fileSystem = $fileSystem;
    $this->httpClient = $httpClient;
    $this->parserManager = $parserManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container = NULL) {
    if (!isset($container)) {
      $container = \Drupal::getContainer();
    }
    return new static(
      $container->get('cache.markdown'),
      $container->get('file_system'),
      $container->get('http_client'),
      $container->get('plugin.manager.markdown.parser')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function load($id) {
    if ($id && ($cache = $this->cache->get($id)) && $cache->data instanceof ParsedMarkdownInterface) {
      return $cache->data;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function loadPath($path, $id = NULL, $parser = NULL, $filter = NULL, AccountInterface $account = NULL, LanguageInterface $language = NULL) {
    $realpath = $this->fileSystem->realpath($path) ?: $path;
    if (!file_exists($realpath)) {
      throw new MarkdownFileNotExistsException($realpath);
    }

    if (!$id) {
      $id = $this->fileSystem->basename($realpath) . Crypt::hashBase64($realpath);
    }

    // Append the file modification time as a cache buster in case it changed.
    $id = "$id:" . filemtime($realpath);
    return $this->load($id) ?: $this->save($id, $this->parse(file_get_contents($realpath) ?: '', $parser, $filter, $account, $language));
  }

  /**
   * {@inheritdoc}
   */
  public function loadUrl($url, $id = NULL, $parser = NULL, $filter = NULL, AccountInterface $account = NULL, LanguageInterface $language = NULL) {
    if ($url instanceof Url) {
      $url = $url->setAbsolute()->toString();
    }
    else {
      $url = (string) $url;
    }

    if (!$id) {
      $id = $url;
    }

    if ($parsed = $this->load($id)) {
      return $parsed;
    }

    $response = \Drupal::httpClient()->get($url);
    if ($response->getStatusCode() < 200 || $response->getStatusCode() >= 400) {
      throw new MarkdownUrlNotExistsException($url);
    }

    return $this->save($id, $this->parse($response->getBody()->getContents(), $parser, $filter, $account, $language));
  }

  /**
   * {@inheritdoc}
   */
  public function parse($markdown, $parser = NULL, $filter = NULL, AccountInterface $account = NULL, LanguageInterface $language = NULL) {
    return $this->getParser($parser, $filter, $account)->parse($markdown, $language);
  }

  /**
   * {@inheritdoc}
   */
  public function getParser($parser = NULL, $filter = NULL, AccountInterface $account = NULL) {
    if (!$this->parser) {
      $this->parser = $this->parserManager->createInstance($parser, [
        'filter' => $filter,
        'account' => $account,
      ]);
    }
    return $this->parser;
  }

  /**
   * {@inheritdoc}
   */
  public function save($id, ParsedMarkdownInterface $parsed) {
    $this->cache->set($id, $parsed, $parsed->getExpire());
    return $parsed;
  }

}
