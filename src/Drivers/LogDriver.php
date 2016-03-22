<?php namespace Exolnet\Instruments\Drivers;

use Log;

class LogDriver extends Driver
{
	/**
	 * Increment a metric
	 *
	 * @param  string|array $metrics
	 * @param  int $delta Value to decrement the metric by
	 * @param  int $sampleRate Sample rate of metric
	 * @return $this
	 */
	public function increment($metrics, $delta = 1, $sampleRate = 1)
	{
		return $this->send('increment', $metrics, $delta);
	}

	/**
	 * Decrement a metric
	 *
	 * @param  string|array $metrics Metric(s) to decrement
	 * @param  int $delta Value to increment the metric by
	 * @param  int $sampleRate Sample rate of metric
	 * @return $this
	 */
	public function decrement($metrics, $delta = 1, $sampleRate = 1)
	{
		return $this->send('decrement', $metrics, $delta);
	}

	/**
	 * Timing
	 *
	 * @param  string $metric Metric to track
	 * @param  float $time Time in milliseconds
	 * @return $this
	 */
	public function timing($metric, $time)
	{
		return $this->send('timing', $metric, $time.' ms');
	}

	/**
	 * Gauges
	 *
	 * @param  string $metric Metric to gauge
	 * @param  int $value Set the value of the gauge
	 * @return $this
	 */
	public function gauge($metric, $value)
	{
		return $this->send('gauge', $metric, $value);
	}

	/**
	 * Sets - count the number of unique values passed to a key
	 *
	 * @param string $metric
	 * @param mixed $value
	 * @return $this
	 */
	public function set($metric, $value)
	{
		return $this->send('set', $metric, $value);
	}

	/**
	 * @param string $method
	 * @param string|array $metrics
	 * @param mixed $value
	 * @return $this
	 */
	private function send($method, $metrics, $value)
	{
		$metrics = implode(', ', (array)$metrics);

		Log::info('[Instruments] '. $method .' '. $metrics .': '. $value);

		return $this;
	}
}
