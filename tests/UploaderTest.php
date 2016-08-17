<?php

use Illuminate\Contracts\Foundation\Application as ApplicationContract;
use Illuminate\Filesystem\Filesystem;
use Mockery as m;
use Recca0120\Upload\Api;
use Recca0120\Upload\Uploader;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Response;

class UploaderTest extends PHPUnit_Framework_TestCase
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
        $uploader = new Uploader($api, $filesystem, $app);

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

        $uploader->receive($name, function ($r) use ($file) {
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
        $filesystem = new Filesystem();
        $app = m::mock(ApplicationContract::class);
        $file = m::mock(UploadedFile::class);
        $uploader = new Uploader($api, $filesystem, $app);

        /*
        |------------------------------------------------------------
        | Expectation
        |------------------------------------------------------------
        */

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

        $uploader->receive($name, function ($r) use ($file) {
            $this->assertSame($file, $r);
        });
    }

    /**
     * @expectedException \Recca0120\Upload\UploadException
     */
    public function test_cannot_read_file()
    {
        /*
        |------------------------------------------------------------
        | Set
        |------------------------------------------------------------
        */

        $name = 'file';
        $api = m::mock(Api::class);
        $filesystem = new Filesystem();
        $app = m::mock(ApplicationContract::class);
        $file = m::mock(UploadedFile::class);
        $response = m::mock(Response::class);
        $uploader = new Uploader($api, $filesystem, $app);

        /*
        |------------------------------------------------------------
        | Expectation
        |------------------------------------------------------------
        */

        $app->shouldReceive('storagePath')->andReturn(__DIR__);

        $api
            ->shouldReceive('setName')->with($name)->once()->andReturn(false)
            ->shouldReceive('hasChunks')->once()->andReturn(true)
            ->shouldReceive('getResourceName')->once()->andReturn($name)
            ->shouldReceive('getStartOffset')->once()->andReturn(10)
            ->shouldReceive('getPartialName')->once()->andReturn('bbb');

        /*
        |------------------------------------------------------------
        | Assertion
        |------------------------------------------------------------
        */

        $uploader->receive($name, function ($r) use ($response) {
            return $response;
        });

        @rmdir(__DIR__.'/uploadchunks');
    }

    public function test_receive_chunked_file_completed()
    {
        /*
        |------------------------------------------------------------
        | Set
        |------------------------------------------------------------
        */

        $name = 'file';
        $api = m::mock(Api::class);
        $filesystem = new Filesystem();
        $app = m::mock(ApplicationContract::class);
        $file = m::mock(UploadedFile::class);
        $response = m::mock(Response::class);
        $uploader = new Uploader($api, $filesystem, $app);

        /*
        |------------------------------------------------------------
        | Expectation
        |------------------------------------------------------------
        */

        $app->shouldReceive('storagePath')->andReturn(__DIR__);

        $api
            ->shouldReceive('setName')->with($name)->once()->andReturn(false)
            ->shouldReceive('hasChunks')->once()->andReturn(true)
            ->shouldReceive('getResourceName')->once()->andReturn(__FILE__)
            ->shouldReceive('getStartOffset')->once()->andReturn(10)
            ->shouldReceive('getPartialName')->once()->andReturn($name)
            ->shouldReceive('isCompleted')->once()->andReturn(true)
            ->shouldReceive('getOriginalName')->once()->andReturn($name)
            ->shouldReceive('getMimeType')->once()->andReturn('')
            ->shouldReceive('completedResponse')->with($response);

        /*
        |------------------------------------------------------------
        | Assertion
        |------------------------------------------------------------
        */

        $uploader->receive($name, function ($r) use ($response) {
            return $response;
        });

        $filesystem->deleteDirectory(__DIR__.'/uploadchunks', true);
        @rmdir(__DIR__.'/uploadchunks');
    }
}
