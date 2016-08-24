<?php

use Illuminate\Contracts\Foundation\Application as ApplicationContract;
use Mockery as m;
use Recca0120\Upload\Adapter;
use Recca0120\Upload\Api;
use Recca0120\Upload\Filesystem;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Response;

class AdapterTest extends PHPUnit_Framework_TestCase
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
        $api = m::mock(Api::class);
        $filesystem = m::mock(Filesystem::class);
        $app = m::mock(ApplicationContract::class);
        $file = m::mock(UploadedFile::class);
        $adapter = new Adapter($api, $filesystem, $app);

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
        $api = m::mock(Api::class);
        $filesystem = m::mock(Filesystem::class);
        $app = m::mock(ApplicationContract::class);
        $file = m::mock(UploadedFile::class);
        $adapter = new Adapter($api, $filesystem, $app);

        /*
        |------------------------------------------------------------
        | Expectation
        |------------------------------------------------------------
        */

        $filesystem
            ->shouldReceive('isDirectory')->once()->andReturn(true)
            ->shouldReceive('appendStream')->once();

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
            ->shouldReceive('chunkedResponse')->with(m::type(Response::class));

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

        $file = __FILE__;
        $name = basename($file);
        $api = m::mock(Api::class);
        $filesystem = m::mock(Filesystem::class);
        $app = m::mock(ApplicationContract::class);
        $file = m::mock(UploadedFile::class);
        $response = m::mock(Response::class);
        $adapter = new Adapter($api, $filesystem, $app);

        /*
        |------------------------------------------------------------
        | Expectation
        |------------------------------------------------------------
        */

        $filesystem
            ->shouldReceive('isDirectory')->andReturn(false)
            ->shouldReceive('makeDirectory')
            ->shouldReceive('appendStream')->once()
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

        $app->shouldReceive('storagePath')->andReturn(__DIR__);

        $api
            ->shouldReceive('setName')->with($name)->once()->andReturn(false)
            ->shouldReceive('hasChunks')->once()->andReturn(true)
            ->shouldReceive('getResourceName')->once()->andReturn($name)
            ->shouldReceive('getStartOffset')->once()->andReturn(10)
            ->shouldReceive('getPartialName')->once()->andReturn('../'.$name)
            ->shouldReceive('isCompleted')->once()->andReturn(true)
            ->shouldReceive('getOriginalName')->once()->andReturn($name)
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
