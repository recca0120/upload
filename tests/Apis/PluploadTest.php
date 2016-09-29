<?php

use Mockery as m;
use Recca0120\Upload\Apis\Plupload;

class PluploadTest extends PHPUnit_Framework_TestCase
{
    public function tearDown()
    {
        m::close();
    }

    public function test_api()
    {
        /*
        |------------------------------------------------------------
        | Set
        |------------------------------------------------------------
        */

        $request = m::mock('Illuminate\Http\Request');
        $response = m::mock('Symfony\Component\HttpFoundation\Response');
        $file = m::mock('Symfony\Component\HttpFoundation\File\UploadedFile');

        /*
        |------------------------------------------------------------
        | Expectation
        |------------------------------------------------------------
        */

        $request
            ->shouldReceive('get')->with('name')->twice()->andReturn('foo.jpg')
            ->shouldReceive('get')->with('chunks', 1)->twice()->andReturn('8')
            ->shouldReceive('get')->with('chunk', 1)->twice()->andReturn('6')
            ->shouldReceive('get')->with('chunks')->once()->andReturn('8')
            ->shouldReceive('get')->with('token')->andReturn(null)
            ->shouldReceive('file')->with('file')->twice()->andReturn($file)
            ->shouldReceive('header')->with('content-length')->once()->andReturn('1049073');

        $response
            ->shouldReceive('getData')->once()->andReturn([])
            ->shouldReceive('setData')->once()->with([
                'jsonrpc' => '2.0',
                'result' => [],
            ]);

        $file
            ->shouldReceive('getMimeType')->once()->andReturn('image/jpg')
            ->shouldReceive('getPathname')->once()->andReturn('php://input');

        /*
        |------------------------------------------------------------
        | Assertion
        |------------------------------------------------------------
        */
        $api = new Plupload($request);
        $api->setName('file');
        $originalName = $api->getOriginalName();
        $this->assertSame($originalName, 'foo.jpg');
        $this->assertSame($api->hasChunks(), true);
        $this->assertSame($api->getStartOffset(), 6294438);
        $this->assertSame($api->getMimeType(), 'image/jpg');
        $this->assertSame($api->getResourceName(), 'php://input');
        $this->assertSame($api->isCompleted(), false);
        $this->assertSame($api->getPartialName(''), md5($originalName).$api->getExtension($originalName).'.part');

        $api->chunkedResponse($response);
        $api->completedResponse($response);
    }
}
