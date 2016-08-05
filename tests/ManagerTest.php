<?php

use Illuminate\Contracts\Foundation\Application as ApplicationContract;
use Mockery as m;
use Recca0120\Upload\FileAPI;
use Recca0120\Upload\Manager;
use Recca0120\Upload\Plupload;
use Recca0120\Upload\Uploader;

class ManagerTest extends PHPUnit_Framework_TestCase
{
    public function tearDown()
    {
        m::close();
    }

    public function testFileAPI()
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
            ->shouldReceive('make')->with(Uploader::class, m::type('array'))
            ->shouldReceive('make')->with(FileAPI::class);

        /*
        |------------------------------------------------------------
        | Assertion
        |------------------------------------------------------------
        */

        $manager = new Manager($app);
        $manager->driver();
    }

    public function testPlupload()
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
            ->shouldReceive('make')->with(Uploader::class, m::type('array'))
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
