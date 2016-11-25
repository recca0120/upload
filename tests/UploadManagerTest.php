<?php

use Mockery as m;
use Recca0120\Upload\Apis\Plupload;
use Recca0120\Upload\UploadManager;

class UploadManagerTest extends PHPUnit_Framework_TestCase
{
    public function tearDown()
    {
        m::close();
    }

    public function test_fileapi()
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
            ->shouldReceive('make')->with('Recca0120\Upload\ApiAdapter', m::type('array'))->once()
            ->shouldReceive('make')->with('Recca0120\Upload\Apis\FileAPI')->once()
            ->shouldReceive('offsetGet')->with('config')->once()->andReturn($config);

        $config->shouldReceive('offsetGet')->with('upload')->once()->andReturn([]);

        /*
        |------------------------------------------------------------
        | Assertion
        |------------------------------------------------------------
        */

        $manager = new UploadManager($app);
        $manager->driver();
    }

    public function test_plupload()
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
            ->shouldReceive('make')->with('Recca0120\Upload\ApiAdapter', m::type('array'))->once()
            ->shouldReceive('make')->with('Recca0120\Upload\Apis\Plupload')->once()
            ->shouldReceive('offsetGet')->with('config')->once()->andReturn($config);

        $config->shouldReceive('offsetGet')->with('upload')->once()->andReturn([]);

        /*
        |------------------------------------------------------------
        | Assertion
        |------------------------------------------------------------
        */

        $manager = new UploadManager($app);
        $manager->driver('plupload');
    }
}
