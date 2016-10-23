<?php

use Mockery as m;
use Recca0120\Upload\ApiAdapter;

class ApiAdapterTest extends PHPUnit_Framework_TestCase
{
    public function tearDown()
    {
        m::close();
    }

    public function test_receive_single_file()
    {
        /*
        |------------------------------------------------------------
        | Set
        |------------------------------------------------------------
        */

        $name = 'file';
        $api = m::mock('Recca0120\Upload\Apis\Api');
        $filesystem = m::mock('Recca0120\Upload\Filesystem');
        $app = m::mock('Illuminate\Contracts\Foundation\Application');
        $file = m::mock('Symfony\Component\HttpFoundation\File\UploadedFile');
        $adapter = new ApiAdapter($api, $filesystem, $app);

        /*
        |------------------------------------------------------------
        | Expectation
        |------------------------------------------------------------
        */

        $api
            ->shouldReceive('setName')->with($name)->once()->andReturn(false)
            ->shouldReceive('hasChunks')->once()->andReturn(false)
            ->shouldReceive('getFile')->once()->andReturn($file);

        /*
        |------------------------------------------------------------
        | Assertion
        |------------------------------------------------------------
        */

        $adapter->receive($name, function ($r) use ($file) {
            $this->assertSame($file, $r);
        });
    }

    public function test_receive_chunked_file()
    {
        /*
        |------------------------------------------------------------
        | Set
        |------------------------------------------------------------
        */

        $name = 'file';
        $api = m::mock('Recca0120\Upload\Apis\Api');
        $filesystem = m::mock('Recca0120\Upload\Filesystem');
        $app = m::mock('Illuminate\Contracts\Foundation\Application');
        $file = m::mock('Symfony\Component\HttpFoundation\File\UploadedFile');
        $adapter = new ApiAdapter($api, $filesystem, $app);

        /*
        |------------------------------------------------------------
        | Expectation
        |------------------------------------------------------------
        */

        $filesystem
            ->shouldReceive('isDirectory')->once()->andReturn(true)
            ->shouldReceive('updateStream')->once();

        $app->shouldReceive('storagePath')->andReturn(__DIR__);

        // $filesystem
        //     ->shouldReceive('isDirectory')->with(__DIR__.'/uploadchunks/')->andReturn(false)
        //     ->shouldReceive('makeDirectory')->with(__DIR__.'/uploadchunks/', 0755, true, true);

        $api
            ->shouldReceive('setName')->with($name)->once()->andReturn(false)
            ->shouldReceive('hasChunks')->once()->andReturn(true)
            ->shouldReceive('getResourceName')->once()->andReturn(__FILE__)
            ->shouldReceive('getStartOffset')->once()->andReturn(10)
            ->shouldReceive('getPartialName')->once()->andReturn($name)
            ->shouldReceive('isCompleted')->once()->andReturn(false)
            ->shouldReceive('chunkedResponse')->with(m::type('Symfony\Component\HttpFoundation\Response'));

        /*
        |------------------------------------------------------------
        | Assertion
        |------------------------------------------------------------
        */

        $adapter->receive($name, function ($r) use ($file) {
            $this->assertSame($file, $r);
        });
    }

    public function test_receive_chunked_file_completed()
    {
        /*
        |------------------------------------------------------------
        | Set
        |------------------------------------------------------------
        */

        $filename = __FILE__;
        $name = basename($filename);
        $api = m::mock('Recca0120\Upload\Apis\Api');
        $filesystem = m::mock('Recca0120\Upload\Filesystem');
        $app = m::mock('Illuminate\Contracts\Foundation\Application');
        $file = m::mock('Symfony\Component\HttpFoundation\File\UploadedFile');
        $response = m::mock('Symfony\Component\HttpFoundation\Response');
        $adapter = new ApiAdapter($api, $filesystem, $app);

        /*
        |------------------------------------------------------------
        | Expectation
        |------------------------------------------------------------
        */

        $filesystem
            ->shouldReceive('isDirectory')->andReturn(false)
            ->shouldReceive('makeDirectory')
            ->shouldReceive('updateStream')->once()
            ->shouldReceive('move')->once()
            ->shouldReceive('isFile')->once()->andReturn(true)
            ->shouldReceive('delete')->once()
            ->shouldReceive('files')->once()->andReturn([
                'foo',
                'bar',
            ])
            ->shouldReceive('exists')->with('foo')->andReturn(true)
            ->shouldReceive('lastModified')->with('foo')->andReturn(time() + 601)
            ->shouldReceive('delete')
            ->shouldReceive('exists')->with('bar')->andReturn(false)
            ->shouldReceive('size')->once()->andReturn(filesize(__FILE__));

        $api
            ->shouldReceive('setName')->with($name)->once()->andReturn(false)
            ->shouldReceive('hasChunks')->once()->andReturn(true)
            ->shouldReceive('getResourceName')->once()->andReturn($name)
            ->shouldReceive('getStartOffset')->once()->andReturn(10)
            ->shouldReceive('getPartialName')->once()->andReturn($filename.'.part')
            ->shouldReceive('isCompleted')->once()->andReturn(true)
            ->shouldReceive('getOriginalName')->once()->andReturn(basename($name))
            ->shouldReceive('getMimeType')->once()->andReturn('')
            ->shouldReceive('completedResponse')->with($response);

        /*
        |------------------------------------------------------------
        | Assertion
        |------------------------------------------------------------
        */

        $adapter->receive($name, function ($r) use ($response) {
            return $response;
        });
    }
}
