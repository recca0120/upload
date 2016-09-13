<?php

use Illuminate\Contracts\Foundation\Application;
use Mockery as m;
use Recca0120\Upload\Facades\AjaxUpload;
use Recca0120\Upload\Manager;

class CartFacadeTest extends PHPUnit_Framework_TestCase
{
    public function tearDown()
    {
        m::close();
    }

    public function test_facade()
    {
        /*
        |------------------------------------------------------------
        | Set
        |------------------------------------------------------------
        */

        $app = m::mock(Application::class.','.ArrayAccess::class);
        AjaxUpload::setFacadeApplication($app);

        /*
        |------------------------------------------------------------
        | Expectation
        |------------------------------------------------------------
        */

        $app->shouldReceive('offsetGet')->with(Manager::class)->once();

        /*
        |------------------------------------------------------------
        | Assertion
        |------------------------------------------------------------
        */

        AjaxUpload::getFacadeRoot();
    }
}
