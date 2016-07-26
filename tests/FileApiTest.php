<?php

use Illuminate\Http\Request;
use Mockery as m;
use Recca0120\Upload\FileApi;

class FileApiTest extends PHPUnit_Framework_TestCase
{
    public function tearDown()
    {
        m::close();
    }

    public function testApi()
    {
        /*
        |------------------------------------------------------------
        | Set
        |------------------------------------------------------------
        */

        $request = m::mock(Request::class);

        /*
        |------------------------------------------------------------
        | Expectation
        |------------------------------------------------------------
        */

        $request
            ->shouldReceive('get')->with('name')->twice()->andReturn(null)
            ->shouldReceive('get')->with('token')->andReturn(null)
            ->shouldReceive('header')->with('content-disposition')->twice()->andReturn('attachment; filename=foo.jpg')
            ->shouldReceive('header')->with('content-type')->once()->andReturn('image/jpg')
            ->shouldReceive('header')->with('content-range')->times(3)->andReturn('bytes 5242880-5767167/7845180');
        /*
        |------------------------------------------------------------
        | Assertion
        |------------------------------------------------------------
        */
        $api = new FileApi($request);
        $api->setName('file');
        $originalName = $api->getOriginalName();
        $this->assertSame($originalName, 'foo.jpg');
        $this->assertSame($api->hasChunks(), true);
        $this->assertSame($api->getStartOffset(), 5242880);
        $this->assertSame($api->getMimeType(), 'image/jpg');
        $this->assertSame($api->getResourceName(), 'php://input');
        $this->assertSame($api->isCompleted(), false);
        $this->assertSame($api->getPartialName(), md5($originalName).$api->getExtension($originalName));
    }
}
