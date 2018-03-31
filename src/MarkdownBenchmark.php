<?php

namespace Drupal\markdown;

/**
 * Class MarkdownBenchmark.
 */
class MarkdownBenchmark {

  /**
   * The start time.
   *
   * @var \DateTime
   */
  protected $start;

  /**
   * The end time.
   *
   * @var \DateTime
   */
  protected $end;

  /**
   * The difference between the start and end times.
   *
   * @var \DateInterval
   */
  protected $diff;

  /**
   * The result of the callback.
   *
   * @var mixed
   */
  protected $result;

  /**
   * MarkdownBenchmark constructor.
   *
   * @param float $start
   *   The start microtime(TRUE).
   * @param $end
   *   The end microtime(TRUE).
   * @param mixed $result
   *   The result of what was benchmarked.
   */
  public function __construct($start, $end, $result = NULL) {
    $this->start = \DateTime::createFromFormat('U.u', sprintf('%.6F', $start));
    $this->end = \DateTime::createFromFormat('U.u', sprintf('%.6F', $end));
    $this->diff = $this->start->diff($this->end);
    $this->result = $result;
  }

  /**
   * Creates a new MarkdownBenchmark instance.
   *
   * @param float $start
   *   The start microtime(TRUE).
   * @param $end
   *   The end microtime(TRUE).
   * @param mixed $result
   *   The result of what was benchmarked.
   *
   * @return static
   */
  public static function create($start, $end, $result = NULL) {
    return new static($start, $end, $result);
  }

  /**
   * Retrieves the benchmark difference between start and end times.
   *
   * @return \DateInterval
   *   The benchmark difference.
   */
  public function getDiff() {
    return $this->diff;
  }

  /**
   * Retrieves the amount of milliseconds from the diff.
   *
   * @param bool $format
   *   Flag indicating whether to format the result to two decimals.
   *
   * @return string|float
   *   The milliseconds.
   */
  public function getMilliseconds($format = TRUE) {
    $ms = 0;
    $ms += $this->diff->m * 2630000000;
    $ms += $this->diff->d * 86400000;
    $ms += $this->diff->h * 3600000;
    $ms += $this->diff->i * 60000;
    $ms += $this->diff->s * 1000;
    $ms += $this->diff->f * 1000;
    return $format ? number_format($ms, 2) : $ms;
  }

  /**
   * Retrieves the result of the callback that was invoked.
   *
   * @return mixed
   *   The callback result.
   */
  public function getResult() {
    return $this->result;
  }

  /**
   * Retrieves the benchmark start time.
   *
   * @return \DateTime
   *   The benchmark start time.
   */
  public function getStart() {
    return $this->start;
  }

  /**
   * Retrieves the benchmark end time.
   *
   * @return \DateTime
   *   The benchmark end time.
   */
  public function getEnd() {
    return $this->end;
  }

}
