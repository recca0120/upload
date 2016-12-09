<?php

use Mockery as m;
use Recca0120\Upload\UploadServiceProvider;

class UploadServiceProviderTest extends PHPUnit_Framework_TestCase
{
    public function tearDown()
    {
        m::close();
    }

    public function test_register()
    {
        /*
        |------------------------------------------------------------
        | Arrange
        |------------------------------------------------------------
        */

        $app = m::spy('Illuminate\Contracts\Foundation\Application, ArrayAccess');
        $config = m::spy('Illuminate\Contracts\Config\Repository, ArrayAccess');
        $request = m::spy('Illuminate\Http\Request');
        $filesystem = m::spy('Recca0120\Upload\Filesystem');

        /*
        |------------------------------------------------------------
        | Act
        |------------------------------------------------------------
        */

        $app
            ->shouldReceive('offsetGet')->with('config')->andReturn($config)
            ->shouldReceive('offsetGet')->with('request')->andReturn($request)
            ->shouldReceive('make')->with('Recca0120\Upload\Filesystem')->andReturn($filesystem);

        $config->shouldReceive('get')->andReturn([]);

        $serviceProvider = new UploadServiceProvider($app);
        $serviceProvider->register();

        /*
        |------------------------------------------------------------
        | Assert
        |------------------------------------------------------------
        */

        $app->shouldHaveReceived('singleton')->with('Recca0120\Upload\Filesystem', 'Recca0120\Upload\Filesystem')->once();
        $app->shouldHaveReceived('singleton')->with('Recca0120\Upload\UploadManager', m::on(function ($closure) use ($app) {
            return is_a($closure($app), 'Recca0120\Upload\UploadManager');
        }))->once();
        $app->shouldHaveReceived('offsetGet')->with('request')->twice();
        $app->shouldHaveReceived('make')->with('Recca0120\Upload\Filesystem')->twice();
        $app->shouldHaveReceived('singleton')->with('Recca0120\Upload\Manager', 'Recca0120\Upload\UploadManager')->once();
    }
}

function storage_path()
{
}
