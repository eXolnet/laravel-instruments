<?php namespace Exolnet\Instruments\Drivers;

use Closure;

abstract class Driver
{
	/**
	 * Increment a metric
	 *
	 * @param  string|array $metrics
	 * @param  int $delta Value to decrement the metric by
	 * @param  int $sampleRate Sample rate of metric
	 * @return $this
	 */
	abstract public function increment($metrics, $delta = 1, $sampleRate = 1);

	/**
	 * Decrement a metric
	 *
	 * @param  string|array $metrics Metric(s) to decrement
	 * @param  int $delta Value to increment the metric by
	 * @param  int $sampleRate Sample rate of metric
	 * @return $this
	 */
	abstract public function decrement($metrics, $delta = 1, $sampleRate = 1);

	/**
	 * Timing
	 *
	 * @param  string $metric Metric to track
	 * @param  float $time Time in seconds
	 * @return $this
	 */
	abstract public function timing($metric, $time);

	/**
	 * @param string $metric
	 * @param \Closure $callback
	 * @return mixed
	 */
	public function time($metric, Closure $callback)
	{
		$startTime = microtime(true);
		$value     = $callback();

		$this->timing($metric, microtime(true) - $startTime);

		return $value;
	}

	/**
	 * Gauges
	 *
	 * @param  string $metric Metric to gauge
	 * @param  int $value Set the value of the gauge
	 * @return $this
	 */
	abstract public function gauge($metric, $value);

	/**
	 * Sets - count the number of unique values passed to a key
	 *
	 * @param string $metric
	 * @param mixed $value
	 * @return $this
	 */
	abstract public function set($metric, $value);
}
