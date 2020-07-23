<?php

namespace Drupal\markdown\Util;

use Closure;
use Illuminate\Contracts\Cache\Repository;
use Illuminate\Contracts\Cache\Store;

/**
 * Adapter for integrating Drupal cache with Laravel.
 *
 * @internal
 * @deprecated in markdown:8.x-2.0 will be removed in markdown:3.0.0. No replacement.
 */
class LaravelCacheRepositoryAdapter implements Repository {

  /**
   * The store.
   *
   * @var \Illuminate\Contracts\Cache\Store
   */
  protected $store;

  public function __construct(Store $store) {
    $this->store = $store;
  }

  /**
   * {@inheritdoc}
   */
  public function add($key, $value, $ttl = NULL) {
    if (!$this->has($key)) {
      $this->store->put($key, $value, $ttl);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function clear() {
    return $this->store->flush();
  }

  /**
   * {@inheritdoc}
   */
  public function decrement($key, $value = 1) {
    return $this->store->decrement($key, $value);
  }

  /**
   * {@inheritdoc}
   */
  public function delete($key) {
    return $this->store->forget($key);
  }

  /**
   * {@inheritdoc}
   */
  public function deleteMultiple($keys) {
    foreach ($keys as $key) {
      $this->delete($key);
    }
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function forever($key, $value) {
    return $this->store->forever($key, $value);
  }

  /**
   * {@inheritdoc}
   */
  public function forget($key) {
    return $this->store->forget($key);
  }

  /**
   * {@inheritdoc}
   */
  public function get($key, $default = NULL) {
    $value = $this->store->get($key);
    return isset($value) ? $value : $default;
  }

  /**
   * {@inheritdoc}
   */
  public function getMultiple($keys, $default = NULL) {
    $results = [];
    foreach ($keys as $key) {
      $results[] = $this->get($key, $default);
    }
    return $results;
  }

  /**
   * {@inheritdoc}
   */
  public function getStore() {
    return $this->store;
  }

  /**
   * {@inheritdoc}
   */
  public function has($key) {
    return $this->store->get($key) !== NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function increment($key, $value = 1) {
    return $this->store->increment($key, $value);
  }

  /**
   * {@inheritdoc}
   */
  public function pull($key, $default = NULL) {
    $value = $this->get($key, $default);
    $this->delete($key);
    return $value;
  }

  /**
   * {@inheritdoc}
   */
  public function put($key, $value, $ttl = NULL) {
    $this->store->put($key, $value, $ttl);
  }

  /**
   * {@inheritdoc}
   */
  public function remember($key, $ttl, Closure $callback) {
    $value = $this->get($key);
    if (!isset($value)) {
      $value = $callback();
      $this->put($key, $value, $ttl);
    }
    return $value;
  }

  /**
   * {@inheritdoc}
   */
  public function rememberForever($key, Closure $callback) {
    $value = $this->get($key);
    if (!isset($value)) {
      $value = $callback();
      $this->forever($key, $value);
    }
    return $value;
  }

  /**
   * {@inheritdoc}
   */
  public function sear($key, Closure $callback) {
    $value = $this->get($key);
    if (!isset($value)) {
      $value = $callback();
      $this->forever($key, $value);
    }
    return $value;
  }

  /**
   * {@inheritdoc}
   */
  public function set($key, $value, $ttl = NULL) {
    return $this->store->put($key, $value, $ttl);
  }

  /**
   * {@inheritdoc}
   */
  public function setMultiple($values, $ttl = NULL) {
    return $this->store->putMany((array) $values, $ttl);
  }

}
