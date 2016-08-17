<?php

use Illuminate\Http\Request;
use Mockery as m;
use Recca0120\Upload\FileAPI;
use Symfony\Component\HttpFoundation\Response;

class FileAPITest extends PHPUnit_Framework_TestCase
{
    public function tearDown()
    {
        m::close();
    }

    public function test_request_has_name()
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
            ->shouldReceive('header')->with('content-range')
            ->shouldReceive('get')->with('name')->once()->andReturn('foo.jpg');
        /*
        |------------------------------------------------------------
        | Assertion
        |------------------------------------------------------------
        */
        $api = new FileAPI($request);
        $api->setName('file');
        $originalName = $api->getOriginalName();
        $this->assertSame($originalName, 'foo.jpg');
    }

    public function test_api()
    {
        /*
        |------------------------------------------------------------
        | Set
        |------------------------------------------------------------
        */

        $request = m::mock(Request::class);
        $response = new Response(null, 201);

        /*
        |------------------------------------------------------------
        | Expectation
        |------------------------------------------------------------
        */

        $start = 5242880;
        $end = 5767167;
        $total = 7845180;

        $request
            ->shouldReceive('get')->with('name')->twice()->andReturn(null)
            ->shouldReceive('get')->with('token')->andReturn(null)
            ->shouldReceive('header')->with('content-disposition')->twice()->andReturn('attachment; filename=foo.jpg')
            ->shouldReceive('header')->with('content-type')->once()->andReturn('image/jpg')
            ->shouldReceive('header')->with('content-range')->once()->andReturn('bytes '.$start.'-'.$end.'/'.$total);

        /*
        |------------------------------------------------------------
        | Assertion
        |------------------------------------------------------------
        */
        $api = new FileAPI($request);
        $api->setName('file');
        $originalName = $api->getOriginalName();
        $this->assertSame($originalName, 'foo.jpg');
        $this->assertSame($api->hasChunks(), true);
        $this->assertSame($api->getStartOffset(), $start);
        $this->assertSame($api->getMimeType(), 'image/jpg');
        $this->assertSame($api->getResourceName(), 'php://input');
        $this->assertSame($api->isCompleted(), false);
        $this->assertSame($api->getPartialName(), md5($originalName).$api->getExtension($originalName));

        $response = $api->chunkedResponse($response);
        $this->assertSame($response->headers->get('X-Last-Known-Byte'), $end);
        $response = $api->completedResponse($response);

        $api->getMaxFilesize();
    }
}
