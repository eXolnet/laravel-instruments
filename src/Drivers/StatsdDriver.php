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
	 * @param array $options
	 */
	public function __construct(Client $client, array $options = [])
	{
		parent::__construct($options);

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
	 * @param string $metric
	 * @return string
	 */
	protected function formatMetric($metric)
	{
		$tags = $this->getAllTags();

		if (empty($tags)) {
			return $metric;
		}

		$this->clearFlashTags();

		if (array_get($this->options, 'stripTagKeys')) {
			$flattenTags = implode('.', array_values($tags));

			$dotPosition = strpos($metric, '.') ?: strlen($metric);

			return substr($metric, 0, $dotPosition) .'.'. $flattenTags . substr($metric, $dotPosition);
		}

		return $metric .','. implode(',', array_map(function ($value, $key) {
			return $key .'='. $value;
		}, $tags, array_keys($tags)));
	}

	/**
	 * @param array|string $metrics
	 * @return array|string
	 */
	protected function formatMetrics($metrics)
	{
		if ( ! is_array($metrics)) {
			return $this->formatMetric($metrics);
		}

		return array_map([$this, 'formatMetric'], $metrics);
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
		$this->client->increment($this->formatMetrics($metrics), $delta, $sampleRate);

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
		$this->client->decrement($this->formatMetrics($metrics), $delta, $sampleRate);

		return $this;
	}

	/**
	 * Timing
	 *
	 * @param  string $metric Metric to track
	 * @param  float $time Time in seconds
	 * @return $this
	 */
	public function timing($metric, $time)
	{
		// We convert time in seconds because this is what the client requires.
		$this->client->timing($this->formatMetrics($metric), round(1000 * $time, 4));

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
		$this->client->gauge($this->formatMetrics($metric), $value);

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
		$this->client->set($this->formatMetrics($metric), $value);

		return $this;
	}
}
