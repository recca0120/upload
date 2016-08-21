<?php

use Illuminate\Contracts\Foundation\Application as ApplicationContract;
use Mockery as m;
use Recca0120\Upload\Adapter;
use Recca0120\Upload\FileAPI;
use Recca0120\Upload\Manager;
use Recca0120\Upload\Plupload;

class ManagerTest extends PHPUnit_Framework_TestCase
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

        $app = m::mock(ApplicationContract::class);

        /*
        |------------------------------------------------------------
        | Expectation
        |------------------------------------------------------------
        */

        $app
            ->shouldReceive('make')->with(Adapter::class, m::type('array'))
            ->shouldReceive('make')->with(FileAPI::class);

        /*
        |------------------------------------------------------------
        | Assertion
        |------------------------------------------------------------
        */

        $manager = new Manager($app);
        $manager->driver();
    }

    public function test_plupload()
    {
        /*
        |------------------------------------------------------------
        | Set
        |------------------------------------------------------------
        */

        $app = m::mock(ApplicationContract::class);

        /*
        |------------------------------------------------------------
        | Expectation
        |------------------------------------------------------------
        */

        $app
            ->shouldReceive('make')->with(Adapter::class, m::type('array'))
            ->shouldReceive('make')->with(Plupload::class);

        /*
        |------------------------------------------------------------
        | Assertion
        |------------------------------------------------------------
        */

        $manager = new Manager($app);
        $manager->driver('plupload');
    }
}
