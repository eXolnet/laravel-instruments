<?php namespace Exolnet\Instruments\Tests;

use Exolnet\Bento\BentoFacade;
use Mockery;
use PHPUnit\Framework\TestCase;

abstract class UnitTest extends TestCase
{
    public function tearDown()
    {
        Mockery::close();
    }
}
