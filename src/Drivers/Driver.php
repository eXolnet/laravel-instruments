<?php

namespace Exolnet\Instruments\Drivers;

use Closure;

abstract class Driver
{
    /**
     * @var array
     */
    protected $options = [];

    /**
     * @var array
     */
    protected $tags = [];

    /**
     * @var array
     */
    protected $flashTags = [];

    /**
     * @param array $options
     */
    public function __construct(array $options = [])
    {
        $this->options = $options;
    }

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

    /**
     * @return array
     */
    public function getTags()
    {
        return $this->tags;
    }

    /**
     * @param string $tags
     * @return $this
     */
    public function setTags($tags)
    {
        $this->tags = $tags;

        return $this;
    }

    /**
     * @return $this
     */
    public function clearTags()
    {
        $this->tags = [];

        return $this;
    }

    /**
     * @param array $tags
     */
    public function tags(array $tags)
    {
        $this->tags = array_merge($this->tags, $tags);
    }

    /**
     * @return array
     */
    public function getFlashTags()
    {
        return $this->flashTags;
    }

    /**
     * @param string $flashTags
     * @return $this
     */
    public function setFlashTags($flashTags)
    {
        $this->flashTags = $flashTags;

        return $this;
    }

    /**
     * @return $this
     */
    public function clearFlashTags()
    {
        $this->flashTags = [];

        return $this;
    }

    /**
     * @param array $flashTags
     * @return $this
     */
    public function flashTags(array $flashTags)
    {
        $this->flashTags = array_merge($this->flashTags, $flashTags);

        return $this;
    }

    /**
     * @return array
     */
    public function getAllTags()
    {
        return array_merge($this->tags, $this->flashTags);
    }
}
