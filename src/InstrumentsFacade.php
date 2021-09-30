<?php

namespace Exolnet\Instruments\Facade;

use Illuminate\Support\Facades\Facade;

class InstrumentsFacade extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'instruments';
    }
}
