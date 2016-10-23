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

        $app = m::mock('Illuminate\Contracts\Foundation\Application, ArrayAccess');
        $config = m::mock('Illuminate\Contracts\Config\Repository, ArrayAccess');

        /*
        |------------------------------------------------------------
        | Expectation
        |------------------------------------------------------------
        */

        $app
            ->shouldReceive('offsetGet')->with('config')->andReturn($config)
            ->shouldReceive('singleton')->with('Recca0120\Upload\Manager', m::type('Closure'))->once()->andReturnUsing(function ($className, $closure) use ($app) {
                return $closure($app);
            });

        $config
            ->shouldReceive('get')->andReturn([])
            ->shouldReceive('set');

        /*
        |------------------------------------------------------------
        | Assertion
        |------------------------------------------------------------
        */

        $serviceProvider = new ServiceProvider($app);
        $serviceProvider->register();
    }
}

function storage_path()
{
    return __DIR__;
}
