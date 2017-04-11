<?php

namespace Recca0120\Upload\Tests;

use Mockery as m;
use Recca0120\Upload\FileAPI;
use PHPUnit\Framework\TestCase;

class FileAPITest extends TestCase
{
    protected function tearDown()
    {
        m::close();
    }

    public function testReceiveSingleFile()
    {
        $request = m::mock('Illuminate\Http\Request');
        $request->shouldReceive('root')->once()->andReturn($root = 'root');
        $api = new FileAPI(
            $config = ['chunks' => $chunksPath = 'foo/', 'storage' => $storagePath = 'foo/'],
            $request,
            $filesystem = m::mock('Recca0120\Upload\Filesystem'),
            $chunkFile = m::mock('Recca0120\Upload\ChunkFile')
        );
        $request->shouldReceive('header')->once()->with('content-disposition')->andReturn('');
        $inputName = 'foo';
        $request->shouldReceive('file')->once()->with($inputName)->andReturn(
            $uploadedFile = m::mock('Symfony\Component\HttpFoundation\File\UploadedFile')
        );
        $this->assertSame($uploadedFile, $api->receive($inputName));
    }

    public function testReceiveChunkedFile()
    {
        $request = m::mock('Illuminate\Http\Request');
        $request->shouldReceive('root')->once()->andReturn($root = 'root');
        $api = new FileAPI(
            $config = ['chunks' => $chunksPath = 'foo/', 'storage' => $storagePath = 'foo/'],
            $request,
            $filesystem = m::mock('Recca0120\Upload\Filesystem'),
            $chunkFile = m::mock('Recca0120\Upload\ChunkFile')
        );
        $filesystem->shouldReceive('isDirectory')->twice()->andReturn(true);

        $start = 5242880;
        $end = 7845180;
        $total = 7845180;
        $request->shouldReceive('get')->once()->with('name')->andReturn('');
        $request->shouldReceive('header')->once()->with('content-disposition')->andReturn(
            'attachment; filename="'.($originalName = 'foo.php').'"'
        );
        $request->shouldReceive('header')->once()->with('content-range')->andReturn(
            $contentRange = 'bytes '.$start.'-'.$end.'/'.$total
        );
        $request->shouldReceive('header')->once()->with('content-type')->andReturn(
            $mimeType = 'foo'
        );
        $request->shouldReceive('get')->once()->with('token')->andReturn($token = 'foo');
        $chunkFile->shouldReceive('setToken')->once()->with($token)->andReturnSelf();
        $chunkFile->shouldReceive('setChunkPath')->once()->with($chunksPath)->andReturnSelf();
        $chunkFile->shouldReceive('setStoragePath')->once()->with($storagePath)->andReturnSelf();
        $chunkFile->shouldReceive('setName')->once()->with($originalName)->andReturnSelf();
        $chunkFile->shouldReceive('setMimeType')->once()->with($mimeType)->andReturnSelf();
        $chunkFile->shouldReceive('appendStream')->once()->with('php://input', $start)->andReturnSelf();
        $chunkFile->shouldReceive('createUploadedFile')->once()->andReturn(
            $uploadedFile = m::mock('Symfony\Component\HttpFoundation\File\UploadedFile')
        );

        $api->receive($inputName = 'foo');
    }

    public function testReceiveChunkedFileWithoutContentRange()
    {
        $request = m::mock('Illuminate\Http\Request');
        $request->shouldReceive('root')->once()->andReturn($root = 'root');
        $api = new FileAPI(
            $config = ['chunks' => $chunksPath = 'foo/', 'storage' => $storagePath = 'foo/'],
            $request,
            $filesystem = m::mock('Recca0120\Upload\Filesystem'),
            $chunkFile = m::mock('Recca0120\Upload\ChunkFile')
        );
        $filesystem->shouldReceive('isDirectory')->twice()->andReturn(true);

        $request->shouldReceive('header')->once()->with('content-range')->andReturn(null);
        $request->shouldReceive('header')->once()->with('content-length')->andReturn($total = 7845180);
        $request->shouldReceive('get')->once()->with('name')->andReturn('');
        $request->shouldReceive('header')->once()->with('content-disposition')->andReturn(
            'attachment; filename="'.($originalName = 'foo.php').'"'
        );
        $request->shouldReceive('header')->once()->with('content-type')->andReturn(
            $mimeType = 'foo'
        );
        $request->shouldReceive('get')->once()->with('token')->andReturn($token = 'foo');
        $chunkFile->shouldReceive('setToken')->once()->with($token)->andReturnSelf();
        $chunkFile->shouldReceive('setChunkPath')->once()->with($chunksPath)->andReturnSelf();
        $chunkFile->shouldReceive('setStoragePath')->once()->with($storagePath)->andReturnSelf();
        $chunkFile->shouldReceive('setName')->once()->with($originalName)->andReturnSelf();
        $chunkFile->shouldReceive('setMimeType')->once()->with($mimeType)->andReturnSelf();
        $chunkFile->shouldReceive('appendStream')->once()->with('php://input', 0)->andReturnSelf();
        $chunkFile->shouldReceive('createUploadedFile')->once()->andReturn(
            $uploadedFile = m::mock('Symfony\Component\HttpFoundation\File\UploadedFile')
        );

        $api->receive($inputName = 'foo');
    }

    public function testReceiveChunkedFileAndThrowChunkedResponseException()
    {
        $request = m::mock('Illuminate\Http\Request');
        $request->shouldReceive('root')->once()->andReturn($root = 'root');
        $api = new FileAPI(
            $config = ['chunks' => $chunksPath = 'foo/', 'storage' => $storagePath = 'foo/'],
            $request,
            $filesystem = m::mock('Recca0120\Upload\Filesystem'),
            $chunkFile = m::mock('Recca0120\Upload\ChunkFile')
        );
        $filesystem->shouldReceive('isDirectory')->twice()->andReturn(true);

        $start = 5242880;
        $end = 5767167;
        $total = 7845180;
        $request->shouldReceive('header')->once()->with('content-range')->andReturn(
            $contentRange = 'bytes '.$start.'-'.$end.'/'.$total
        );
        $request->shouldReceive('get')->once()->with('name')->andReturn('');
        $request->shouldReceive('header')->once()->with('content-disposition')->andReturn(
            'attachment; filename='.($originalName = 'foo.php')
        );
        $request->shouldReceive('header')->once()->with('content-type')->andReturn(
            $mimeType = 'foo'
        );

        $request->shouldReceive('get')->once()->with('token')->andReturn($token = 'foo');
        $chunkFile->shouldReceive('setToken')->once()->with($token)->andReturnSelf();
        $chunkFile->shouldReceive('setChunkPath')->once()->with($chunksPath)->andReturnSelf();
        $chunkFile->shouldReceive('setStoragePath')->once()->with($storagePath)->andReturnSelf();
        $chunkFile->shouldReceive('setName')->once()->with($originalName)->andReturnSelf();
        $chunkFile->shouldReceive('setMimeType')->once()->with($mimeType)->andReturnSelf();
        $chunkFile->shouldReceive('appendStream')->once()->with('php://input', $start)->andReturnSelf();
        $chunkFile->shouldReceive('throwException')->once()->with(json_encode([
            'files' => [
                'name' => $originalName,
                'size' => $end,
                'type' => $mimeType,
            ],
        ]), ['X-Last-Known-Byte' => $end]);

        $api->receive($inputName = 'foo');
    }
}
