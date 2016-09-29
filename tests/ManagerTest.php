<?php

use Mockery as m;
use Recca0120\Upload\Manager;
use Recca0120\Upload\Apis\Plupload;

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

        $app = m::mock('Illuminate\Contracts\Foundation\Application');

        /*
        |------------------------------------------------------------
        | Expectation
        |------------------------------------------------------------
        */

        $app
            ->shouldReceive('make')->with('Recca0120\Upload\ApiAdapter', m::type('array'))
            ->shouldReceive('make')->with('Recca0120\Upload\Apis\FileAPI');

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

        $app = m::mock('Illuminate\Contracts\Foundation\Application');

        /*
        |------------------------------------------------------------
        | Expectation
        |------------------------------------------------------------
        */

        $app
            ->shouldReceive('make')->with('Recca0120\Upload\ApiAdapter', m::type('array'))
            ->shouldReceive('make')->with('Recca0120\Upload\Apis\Plupload');

        /*
        |------------------------------------------------------------
        | Assertion
        |------------------------------------------------------------
        */

        $manager = new Manager($app);
        $manager->driver('plupload');
    }
}
