<?php

use Mockery as m;
use Recca0120\Upload\Uploader\FileAPI;
use Recca0120\Upload\Exception\ChunkedResponseException;

class FileAPITest extends PHPUnit_Framework_TestCase
{
    public function tearDown()
    {
        m::close();
    }

    public function test_upload_single_file()
    {
        /*
        |------------------------------------------------------------
        | Arrange
        |------------------------------------------------------------
        */

        $request = m::spy('Illuminate\Http\Request');
        $filesystem = m::spy('Recca0120\Upload\Filesystem');
        $uploadedFile = m::spy('Symfony\Component\HttpFoundation\File\UploadedFile');
        $name = 'foo';

        /*
        |------------------------------------------------------------
        | Act
        |------------------------------------------------------------
        */

        $request
            ->shouldReceive('file')->with($name)->andReturn($uploadedFile);

        $uploader = new FileAPI($request, $filesystem);

        /*
        |------------------------------------------------------------
        | Assert
        |------------------------------------------------------------
        */

        $this->assertSame($uploadedFile, $uploader->get($name));

        $request->shouldHaveReceived('header')->with('content-range')->once();
        $request->shouldHaveReceived('file')->with($name)->once();
    }

    public function test_upload_chunk_file_throw_chunk_response()
    {
        /*
        |------------------------------------------------------------
        | Arrange
        |------------------------------------------------------------
        */

        $request = m::spy('Illuminate\Http\Request');
        $filesystem = m::spy('Recca0120\Upload\Filesystem');
        $uploadedFile = m::spy('Symfony\Component\HttpFoundation\File\UploadedFile');
        $name = 'foo';
        $path = __DIR__;

        $token = uniqid();
        $file = __FILE__;
        $originalName = basename($file);
        $extension = pathinfo($originalName, PATHINFO_EXTENSION);
        $mimeType = finfo_file(finfo_open(FILEINFO_MIME_TYPE), $file);
        $tmpfile = $path.'/'.md5($originalName.$token).'.'.$extension;

        $start = 5242880;
        $end = 5767167;
        $total = 7845180;

        $contentRange = 'bytes '.$start.'-'.$end.'/'.$total;
        $contentDisposition = 'attachment; filename='.$originalName;
        $input = 'php://input';

        /*
        |------------------------------------------------------------
        | Act
        |------------------------------------------------------------
        */

        $request
            ->shouldReceive('header')->with('content-range')->andReturn($contentRange)
            ->shouldReceive('header')->with('content-type')->andReturn($mimeType)
            ->shouldReceive('header')->with('content-disposition')->andReturn($contentDisposition)
            ->shouldReceive('get')->with('token')->andReturn($token);

        $filesystem
            ->shouldReceive('extension')->andReturn($extension);

        $uploader = new FileAPI($request, $filesystem, $path);

        /*
        |------------------------------------------------------------
        | Assert
        |------------------------------------------------------------
        */

        try {
            $uploader->get($name);
        } catch (ChunkedResponseException $e) {
            $response = $e->getResponse();
            $this->assertSame(201, $response->getStatusCode());
            $this->assertSame($end, $response->headers->get('X-Last-Known-Byte'));
        }

        $request->shouldHaveReceived('header')->with('content-range')->once();
        $request->shouldHaveReceived('get')->with('name')->once();
        $request->shouldHaveReceived('header')->with('content-disposition')->once();
        $request->shouldHaveReceived('header')->with('content-type')->once();
        $filesystem->shouldHaveReceived('extension')->with($originalName)->once();
        $request->shouldHaveReceived('get')->with('token')->once();
        $filesystem->shouldHaveReceived('appendStream')->with($tmpfile.'.part', $input, $start)->once();
    }

    public function test_upload_chunk_file()
    {
        /*
        |------------------------------------------------------------
        | Arrange
        |------------------------------------------------------------
        */

        $request = m::spy('Illuminate\Http\Request');
        $filesystem = m::spy('Recca0120\Upload\Filesystem');
        $uploadedFile = m::spy('Symfony\Component\HttpFoundation\File\UploadedFile');
        $name = 'foo';
        $path = __DIR__;

        $token = uniqid();
        $file = __FILE__;
        $originalName = basename($file);
        $extension = pathinfo($originalName, PATHINFO_EXTENSION);
        $mimeType = finfo_file(finfo_open(FILEINFO_MIME_TYPE), $file);
        $tmpfile = $path.'/'.md5($originalName.$token).'.'.$extension;

        $start = 5242880;
        $end = 7845180;
        $total = 7845180;

        $contentRange = 'bytes '.$start.'-'.$end.'/'.$total;
        $contentDisposition = 'attachment; filename='.$originalName;
        $input = 'php://input';
        $size = $total;

        /*
        |------------------------------------------------------------
        | Act
        |------------------------------------------------------------
        */

        $request
            ->shouldReceive('header')->with('content-range')->andReturn($contentRange)
            ->shouldReceive('header')->with('content-disposition')->andReturn($contentDisposition)
            ->shouldReceive('get')->with('token')->andReturn($token);

        $filesystem
            ->shouldReceive('extension')->with($originalName)->andReturn($extension)
            ->shouldReceive('mimeType')->with($originalName)->andReturn($mimeType)
            ->shouldReceive('move')->with($tmpfile.'.part', $tmpfile)
            ->shouldReceive('size')->with($tmpfile)->andReturn($total);

        $uploader = new FileAPI($request, $filesystem, $path);

        /*
        |------------------------------------------------------------
        | Assert
        |------------------------------------------------------------
        */

        $uploader->get($name);

        $request->shouldHaveReceived('header')->with('content-range')->once();
        $request->shouldHaveReceived('get')->with('name')->once();
        $request->shouldHaveReceived('header')->with('content-disposition')->once();
        $filesystem->shouldHaveReceived('extension')->with($originalName)->once();
        $filesystem->shouldHaveReceived('mimeType')->with($originalName)->once();
        $request->shouldHaveReceived('get')->with('token')->once();
        $filesystem->shouldHaveReceived('appendStream')->with($tmpfile.'.part', $input, $start)->once();
        $filesystem->shouldHaveReceived('move')->with($tmpfile.'.part', $tmpfile)->once();
        $filesystem->shouldHaveReceived('size')->once();
        $filesystem->shouldHaveReceived('createUploadedFile')->with($tmpfile, $originalName, $mimeType, $size)->once();
    }
}
