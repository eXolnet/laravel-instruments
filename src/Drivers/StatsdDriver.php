<?php namespace Exolnet\Instruments\Drivers;

use League\StatsD\Client;

class StatsdDriver extends Driver
{
	/**
	 * @var \League\StatsD\Client
	 */
	private $client;

	/**
	 * StatsdStore constructor.
	 *
	 * @param \League\StatsD\Client $client
	 */
	public function __construct(Client $client)
	{
		$this->client = $client;
	}

	/**
	 * @return \League\StatsD\Client
	 */
	public function getClient()
	{
		return $this->client;
	}

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
		$this->client->increment($metrics, $delta, $sampleRate);

		return $this;
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
		$this->client->decrement($metrics, $delta, $sampleRate);

		return $this;
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
		$this->client->timing($metric, $time);

		return $this;
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
		$this->client->gauge($metric, $value);

		return $this;
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
		$this->client->set($metric, $value);

		return $this;
	}
}
