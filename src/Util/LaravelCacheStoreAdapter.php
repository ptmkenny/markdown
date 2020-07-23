<?php

namespace Drupal\markdown\Util;

use Drupal\Core\Cache\CacheBackendInterface;
use Illuminate\Contracts\Cache\Store;

/**
 * Adapter for integrating Drupal cache with Laravel.
 *
 * @internal
 * @deprecated in markdown:8.x-2.0 will be removed in markdown:3.0.0. No replacement.
 */
class LaravelCacheStoreAdapter implements Store {

  /**
   * The cache backend.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cache;

  /**
   * The cache identifier prefix.
   *
   * @var string
   */
  protected $prefix;

  public function __construct(CacheBackendInterface $cacheBackend, $prefix = NULL) {
    $this->cache = $cacheBackend;
    $this->prefix = $prefix;
  }

  /**
   * {@inheritdoc}
   */
  public function decrement($key, $value = 1) {
    $key = $this->prefixKey($key);
    $value = $this->cache->get($key) - $value;
    $this->cache->set($key, $value);
    return $value;
  }

  /**
   * {@inheritdoc}
   */
  public function flush() {
    $this->cache->deleteAll();
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function forever($key, $value) {
    $key = $this->prefixKey($key);
    $this->cache->set($key, $value);
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function forget($key) {
    $key = $this->prefixKey($key);
    $this->cache->delete($key);
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function get($key) {
    $key = $this->prefixKey($key);
    $cache = $this->cache->get($key);
    return isset($cache->data) ? $cache->data : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getPrefix() {
    return $this->prefix;
  }

  /**
   * {@inheritdoc}
   */
  public function increment($key, $value = 1) {
    $key = $this->prefixKey($key);
    $value = $this->cache->get($key) + $value;
    $this->cache->set($key, $value);
    return $value;
  }

  /**
   * {@inheritdoc}
   */
  public function many(array $keys) {
    $result = [];
    foreach ($keys as $key) {
      $result[$this->prefixKey($key)] = $this->get($key);
    }
    return $result;
  }

  /**
   * Prefixes a key.
   *
   * @param string $key
   *   The key to prefix.
   * @param string $delimiter
   *   Optional. The delimiter to use, defaults to a period.
   *
   * @return string
   *   The prefixed key, if prefix is set.
   */
  protected function prefixKey($key, $delimiter = '.') {
    if ($this->prefix) {
      return "{$this->prefix}$delimiter$key";
    }
    return $key;
  }

  /**
   * {@inheritdoc}
   */
  public function put($key, $value, $seconds) {
    $key = $this->prefixKey($key);
    $this->cache->set($key, $value, REQUEST_TIME + $seconds);
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function putMany(array $values, $seconds) {
    foreach ($values as $key => $value) {
      $this->put($key, $values, $seconds);
    }
    return TRUE;
  }

}
