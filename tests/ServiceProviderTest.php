<?php

use Mockery as m;
use Recca0120\Upload\ServiceProvider;

class ServiceProviderTest extends PHPUnit_Framework_TestCase
{
    public function tearDown()
    {
        m::close();
    }

    public function test_service_provider()
    {
        /*
        |------------------------------------------------------------
        | Set
        |------------------------------------------------------------
        */

        $app = m::mock('Illuminate\Contracts\Foundation\Application');

        /*
        |------------------------------------------------------------
        | Expectation
        |------------------------------------------------------------
        */

        $app->shouldReceive('singleton')->with('Recca0120\Upload\Manager', m::type('Closure'))->once()->andReturnUsing(function ($className, $closure) use ($app) {
            return $closure($app);
        });

        /*
        |------------------------------------------------------------
        | Assertion
        |------------------------------------------------------------
        */

        $serviceProvider = new ServiceProvider($app);
        $serviceProvider->register();
    }
}
