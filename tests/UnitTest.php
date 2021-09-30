<?php namespace Exolnet\Instruments\Tests;

use Mockery;
use PHPUnit\Framework\TestCase;

abstract class UnitTest extends TestCase
{
    public function tearDown(): void
    {
        Mockery::close();
    }
}
