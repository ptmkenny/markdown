<?php

namespace Drupal\markdown;

use Drupal\Core\DependencyInjection\DependencySerializationTrait;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Class MarkdownBenchmarkAverages.
 */
class MarkdownBenchmarkAverages {

  use DependencySerializationTrait;
  use StringTranslationTrait;

  /**
   * A fallback benchmark.
   *
   * @var \Drupal\markdown\MarkdownBenchmark
   */
  protected $fallbackBenchmark;

  /**
   * The number of iterations.
   *
   * @var int
   */
  protected $iterationCount = 10;

  /**
   * An array of benchmarks.
   *
   * @var \Drupal\markdown\MarkdownBenchmark[]
   */
  protected $benchmarks = [];

  /**
   * MarkdownBenchmarkAverages constructor.
   *
   * @param int $iteration_count
   *   The amount of of loop iterations used to average the results of each
   *   MarkdownParser benchmark.
   * @param \Drupal\markdown\MarkdownBenchmark $fallback_benchmark
   *   A fallback benchmark to use if/when there are no benchmarks available.
   */
  public function __construct($iteration_count = 10, MarkdownBenchmark $fallback_benchmark = NULL) {
    $this->iterationCount = $iteration_count;
    $this->fallbackBenchmark = $fallback_benchmark;
  }

  /**
   * Creates a new MarkdownBenchmarkAverages object.
   *
   * @param int $iteration_count
   *   The amount of of loop iterations used to average the results of each
   *   MarkdownParser benchmark.
   * @param \Drupal\markdown\MarkdownBenchmark $fallback
   *   A fallback benchmark to use if/when there are no benchmarks available.
   *
   * @return static
   */
  public static function create($iteration_count = 10, MarkdownBenchmark $fallback = NULL) {
    return new static($iteration_count, $fallback);
  }

  /**
   * Iterates a callback that produces benchmarks.
   *
   * @param callable $callback
   *   A callback.
   * @param array $args
   *   The arguments to provide to the $callback.
   *
   * @return static
   */
  public function iterate(callable $callback, array $args = []) {
    $this->benchmarks = [];

    // Iterate the callback the specified amount of times.
    for ($i = 0; $i < $this->iterationCount; $i++) {
      $benchmarks = (array) call_user_func_array($callback, $args);

      // Verify all benchmarks are the proper object.
      foreach ($benchmarks as $benchmark) {
        if (!($benchmark instanceof MarkdownBenchmark)) {
          throw new \InvalidArgumentException(sprintf('The provided callback must return an instance of \\Drupal\\markdown\\MarkdownBenchmark, got "%s" instead.', (string) $benchmark));
        }

        // Remove the result if this is the last benchmark. This is to reduce
        // the amount of storage needed on the backend.
        if ($this->iterationCount > 1 && $i < $this->iterationCount - 1) {
          $benchmark->clearResult();
        }
      }

      $this->benchmarks = array_merge($this->benchmarks, $benchmarks);
    }

    return $this;
  }

  /**
   * Retrieves the averaged milliseconds from all benchmarks of a certain type.
   *
   * @param string $type
   *   The type of benchmark to retrieve, can be one of:
   *   - parsed
   *   - rendered
   *   - total (default)
   * @param bool $format
   *   Flag indicating whether to format the result to two decimals.
   *
   * @return string|float
   *   The averaged milliseconds.
   */
  public function getAverage($type = 'total', $format = TRUE) {
    $ms = array_map(function ($benchmark) {
      /** @var \Drupal\markdown\MarkdownBenchmark $benchmark */
      return $benchmark->getMilliseconds(FALSE);
    }, $this->getBenchmarks($type));

    if ($ms) {
      $averaged_ms = array_sum($ms) / count($ms);
      return $format ? number_format($averaged_ms, 2) : $averaged_ms;
    }

    return $format ? 'N/A' : -999;
  }

  /**
   * Retrieves the currently set benchmarks.
   *
   * @param string $type
   *   The type of benchmark to retrieve, can be one of:
   *   - parsed
   *   - rendered
   *   - total (default)
   *
   * @return \Drupal\markdown\MarkdownBenchmark[]
   */
  public function getBenchmarks($type = NULL) {
    if ($type === NULL) {
      $benchmarks = $this->benchmarks;
    }
    else {
      $benchmarks = array_filter($this->benchmarks, function ($benchmark) use ($type) {
        /** @type \Drupal\markdown\MarkdownBenchmark $benchmark */
        return $benchmark->getType() === $type;
      });
    }

    return $benchmarks;
  }

  /**
   * Retrieves a fallback benchmark, creating one if necessary.
   *
   * @return \Drupal\markdown\MarkdownBenchmark
   *   A fallback benchmark.
   */
  public function getFallbackBenchmark() {
    if ($this->fallbackBenchmark === NULL) {
      $this->fallbackBenchmark = MarkdownBenchmark::create('fallback', NULL, NULL, $this->t('N/A'));
    }
    return $this->fallbackBenchmark;
  }

  /**
   * Retrieves the last benchmark of a certain type.
   *
   * @param string $type
   *   The type of benchmark to retrieve, can be one of:
   *   - parsed
   *   - rendered
   *   - total (default)
   *
   * @return \Drupal\markdown\MarkdownBenchmark
   *   The last benchmark of $type.
   */
  public function getLastBenchmark($type = 'total') {
    $benchmarks = $this->getBenchmarks($type);
    return array_pop($benchmarks) ?: $this->getFallbackBenchmark();
  }

  /**
   * Retrieves the number of times the benchmarks were iterated over.
   *
   * @return int
   *   The iteration count.
   */
  public function getIterationCount() {
    return $this->iterationCount;
  }

  /**
   * Indicates whether there are benchmarks or not.
   *
   * @return bool
   *   TRUE or FALSE
   */
  public function hasBenchmarks() {
    return !!$this->benchmarks;
  }

}
