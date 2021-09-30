<?php

namespace Exolnet\Instruments;

use Exolnet\Instruments\Drivers\LogDriver;
use Exolnet\Instruments\Drivers\NullDriver;
use Exolnet\Instruments\Drivers\StatsdDriver;
use Exolnet\Instruments\Exceptions\InstrumentsConfigurationException;
use Illuminate\Support\Manager;
use Illuminate\Support\Str;
use League\StatsD\Client;

class InstrumentsManager extends Manager
{
    /**
     * Get the default driver name.
     *
     * @return string
     */
    public function getDefaultDriver()
    {
        return $this->app['config']['instruments.driver'];
    }

    /**
     * @return \Exolnet\Instruments\Drivers\StatsdDriver
     */
    protected function createStatsdDriver()
    {
        $options = config('instruments.statsd');

        $client = new Client();
        $client->configure($options);

        return new StatsdDriver($client, $options);
    }

    /**
     * @return \Exolnet\Instruments\Drivers\LogDriver
     */
    protected function createLogDriver()
    {
        return new LogDriver();
    }

    /**
     * @return \Exolnet\Instruments\Drivers\NullDriver
     */
    protected function createNullDriver()
    {
        return new NullDriver();
    }
}
