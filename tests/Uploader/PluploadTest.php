<?php

use Mockery as m;
use Recca0120\Upload\Uploaders\Plupload;
use Recca0120\Upload\Exceptions\ChunkedResponseException;

class PluploadTest extends PHPUnit_Framework_TestCase
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

        $config = [
            'chunksPath' => __DIR__,
        ];
        $request = m::spy('Illuminate\Http\Request');
        $filesystem = m::spy('Recca0120\Upload\Filesystem');
        $uploadedFile = m::spy('Symfony\Component\HttpFoundation\File\UploadedFile');
        $inputName = 'foo';

        /*
        |------------------------------------------------------------
        | Act
        |------------------------------------------------------------
        */

        $request
            ->shouldReceive('file')->with($inputName)->andReturn($uploadedFile);

        $filesystem
            ->shouldReceive('isDirectory')->with($config['chunksPath'])->andReturn(false)
            ->shouldReceive('files')->with($config['chunksPath'])->andReturn(['file'])
            ->shouldReceive('isFile')->with('file')->andReturn(true)
            ->shouldReceive('lastModified')->with('file')->andReturn(-1);

        $uploader = new Plupload($config, $request, $filesystem);

        /*
        |------------------------------------------------------------
        | Assert
        |------------------------------------------------------------
        */

        $this->assertSame($uploadedFile, $uploader->receive($inputName));

        $filesystem->shouldHaveReceived('isDirectory')->with($config['chunksPath'])->once();
        $filesystem->shouldHaveReceived('makeDirectory')->with($config['chunksPath'], 0777, true, true)->once();
        $request->shouldHaveReceived('get')->with('chunks')->once();
        $request->shouldHaveReceived('file')->with($inputName)->once();
        $filesystem->shouldHaveReceived('files')->with($config['chunksPath'])->once();
        $filesystem->shouldHaveReceived('isFile')->with('file')->once();
        $filesystem->shouldHaveReceived('lastModified')->with('file')->once();
        $filesystem->shouldHaveReceived('delete')->with('file')->once();
    }

    public function test_upload_chunk_file_throw_chunk_response()
    {
        /*
        |------------------------------------------------------------
        | Arrange
        |------------------------------------------------------------
        */

        $config = [
            'chunksPath' => __DIR__,
        ];
        $request = m::spy('Illuminate\Http\Request');
        $filesystem = m::spy('Recca0120\Upload\Filesystem');
        $uploadedFile = m::spy('Symfony\Component\HttpFoundation\File\UploadedFile');
        $inputName = 'foo';

        $chunks = 8;
        $chunk = 6;
        $contentLength = 1049073;

        /*
        |------------------------------------------------------------
        | Act
        |------------------------------------------------------------
        */

        $request
            ->shouldReceive('get')->with('chunks')->andReturn($chunks)
            ->shouldReceive('get')->with('chunk')->andReturn($chunk)
            ->shouldReceive('header')->with('content-length')->andReturn($contentLength)
            ->shouldReceive('file')->with($inputName)->andReturn($uploadedFile);

        $filesystem
            ->shouldReceive('isDirectory')->with($config['chunksPath'])->andReturn(false);

        $uploader = new Plupload($config, $request, $filesystem);

        /*
        |------------------------------------------------------------
        | Assert
        |------------------------------------------------------------
        */

        try {
            $uploader->receive($inputName);
        } catch (ChunkedResponseException $e) {
            $response = $e->getResponse();
            $this->assertSame(201, $response->getStatusCode());
        }

        $filesystem->shouldHaveReceived('isDirectory')->with($config['chunksPath'])->once();
        $filesystem->shouldHaveReceived('makeDirectory')->with($config['chunksPath'], 0777, true, true)->once();
        $request->shouldHaveReceived('get')->with('chunks')->once();
        $request->shouldHaveReceived('file')->with($inputName)->once();
        $request->shouldHaveReceived('get')->with('chunk')->once();
        $request->shouldHaveReceived('get')->with('name')->once();
    }

    public function test_upload_chunk_file()
    {
        /*
        |------------------------------------------------------------
        | Arrange
        |------------------------------------------------------------
        */

        $config = [
            'chunksPath' => __DIR__,
        ];
        $request = m::spy('Illuminate\Http\Request');
        $filesystem = m::spy('Recca0120\Upload\Filesystem');
        $uploadedFile = m::spy('Symfony\Component\HttpFoundation\File\UploadedFile');
        $inputName = 'foo';

        $token = uniqid();
        $file = __FILE__;
        $originalName = basename($file);
        $extension = pathinfo($originalName, PATHINFO_EXTENSION);
        $mimeType = finfo_file(finfo_open(FILEINFO_MIME_TYPE), $file);
        $tmpfile = $config['chunksPath'].'/'.md5($originalName.$token).'.'.$extension;
        $tmpfileExtension = Plupload::TMPFILE_EXTENSION;

        $chunks = 8;
        $chunk = 7;
        $contentLength = 1049073;
        $input = 'php://input';

        $start = $chunk * $contentLength;
        $size = $chunks * $contentLength;

        /*
        |------------------------------------------------------------
        | Act
        |------------------------------------------------------------
        */

        $request
            ->shouldReceive('file')->with($inputName)->andReturn($uploadedFile)
            ->shouldReceive('get')->with('chunks')->andReturn($chunks)
            ->shouldReceive('get')->with('chunk')->andReturn($chunk)
            ->shouldReceive('header')->with('content-length')->andReturn($contentLength)
            ->shouldReceive('get')->with('name')->andReturn($originalName)
            ->shouldReceive('get')->with('token')->andReturn($token);

        $uploadedFile
            ->shouldReceive('getMimeType')->andReturn($mimeType)
            ->shouldReceive('getPathname')->andReturn($input);

        $filesystem
            ->shouldReceive('isDirectory')->with($config['chunksPath'])->andReturn(false)
            ->shouldReceive('extension')->with($originalName)->andReturn($extension)
            ->shouldReceive('move')->with($tmpfile.$tmpfileExtension, $tmpfile)
            ->shouldReceive('size')->with($tmpfile)->andReturn($size)
            ->shouldReceive('files')->with($config['chunksPath'])->andReturn(['file'])
            ->shouldReceive('isFile')->with('file')->andReturn(true)
            ->shouldReceive('lastModified')->with('file')->andReturn(-1);

        $uploader = new Plupload($config, $request, $filesystem);

        /*
        |------------------------------------------------------------
        | Assert
        |------------------------------------------------------------
        */

        $uploader->receive($inputName);

        $filesystem->shouldHaveReceived('isDirectory')->with($config['chunksPath'])->once();
        $filesystem->shouldHaveReceived('makeDirectory')->with($config['chunksPath'], 0777, true, true)->once();
        $request->shouldHaveReceived('get')->with('chunks')->once();
        $request->shouldHaveReceived('file')->with($inputName)->once();
        $request->shouldHaveReceived('get')->with('chunk')->once();
        $request->shouldHaveReceived('header')->with('content-length')->once();
        $request->shouldHaveReceived('get')->with('name')->once();
        $request->shouldHaveReceived('get')->with('token')->once();
        $filesystem->shouldHaveReceived('extension')->with($originalName)->once();
        $filesystem->shouldHaveReceived('appendStream')->with($tmpfile.$tmpfileExtension, $input, $start)->once();
        $filesystem->shouldHaveReceived('createUploadedFile')->with($tmpfile, $originalName, $mimeType, $size)->once();
        $filesystem->shouldHaveReceived('files')->with($config['chunksPath'])->once();
        $filesystem->shouldHaveReceived('isFile')->with('file')->once();
        $filesystem->shouldHaveReceived('lastModified')->with('file')->once();
        $filesystem->shouldHaveReceived('delete')->with('file')->once();
    }

    public function test_completed_response()
    {
        /*
        |------------------------------------------------------------
        | Arrange
        |------------------------------------------------------------
        */

        $config = [
            'chunksPath' => __DIR__,
        ];
        $request = m::spy('Illuminate\Http\Request');
        $filesystem = m::spy('Recca0120\Upload\Filesystem');
        $response = m::spy('Illuminate\Http\JsonResponse');
        $data = ['foo' => 'bar'];

        /*
        |------------------------------------------------------------
        | Act
        |------------------------------------------------------------
        */

        $response
            ->shouldReceive('getData')->andReturn($data);

        $uploader = new Plupload($config, $request, $filesystem);

        /*
        |------------------------------------------------------------
        | Assert
        |------------------------------------------------------------
        */

        $this->assertSame($response, $uploader->completedResponse($response));

        $response->shouldHaveReceived('setData')->with([
            'jsonrpc' => '2.0',
            'result' => $data,
        ]);
    }

    public function test_delete_uploaded_file()
    {
        /*
        |------------------------------------------------------------
        | Arrange
        |------------------------------------------------------------
        */

        $config = [
            'chunksPath' => __DIR__,
        ];
        $request = m::spy('Illuminate\Http\Request');
        $filesystem = m::spy('Recca0120\Upload\Filesystem');
        $uploadedFile = m::spy('Symfony\Component\HttpFoundation\File\UploadedFile');
        $file = 'test';

        /*
        |------------------------------------------------------------
        | Act
        |------------------------------------------------------------
        */

        $uploadedFile
            ->shouldReceive('getPathname')->andReturn($file);

        $filesystem
            ->shouldReceive('isFile')->with($file)->andReturn(true)
            ->shouldReceive('delete')->with($file)->andReturn(true);

        $uploader = new Plupload($config, $request, $filesystem);

        /*
        |------------------------------------------------------------
        | Assert
        |------------------------------------------------------------
        */

        $this->assertSame($uploader, $uploader->deleteUploadedFile($uploadedFile));
        $uploadedFile->shouldHaveReceived('getPathname')->once();
        $filesystem->shouldHaveReceived('isFile')->with($file)->once();
        $filesystem->shouldHaveReceived('delete')->with($file)->once();
    }
}
