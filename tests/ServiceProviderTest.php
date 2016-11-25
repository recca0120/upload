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
        $aliasName = 'Recca0120\Upload\Manager';

        /*
        |------------------------------------------------------------
        | Expectation
        |------------------------------------------------------------
        */

        $app
            ->shouldReceive('offsetGet')->with('config')->andReturn($config)
            ->shouldReceive('singleton')->with('Recca0120\Upload\UploadManager', m::type('Closure'))->once()->andReturnUsing(function ($className, $closure) use ($app) {
                return $closure($app);
            })
            ->shouldReceive('singleton')->with($aliasName, 'Recca0120\Upload\UploadManager');

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

        $this->assertTrue(class_exists($aliasName));
    }
}

function storage_path()
{
    return __DIR__;
}
