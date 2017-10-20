<?php namespace Exolnet\Instruments\Tests\Unit;

use Exolnet\Instruments\Drivers\StatsdDriver;
use Exolnet\Instruments\Tests\UnitTest;
use League\StatsD\Client;
use Mockery as m;

class StatsdDriverTest extends UnitTest
{
	/**
	 * @var \Mockery\MockInterface|\League\StatsD\Client
	 */
	protected $client;

	/**
	 * @var \Mockery\MockInterface|\Exolnet\Instruments\Drivers\Driver
	 */
	protected $driver;

	/**
	 * @return void
	 */
	public function setUp()
	{
		$this->client = m::mock(Client::class);

		$this->driver = new StatsdDriver($this->client);
	}

	/**
	 * @return void
	 */
	public function testFeatureIsInstantiable()
	{
		$this->assertInstanceOf(StatsdDriver::class, $this->driver);
	}
}
