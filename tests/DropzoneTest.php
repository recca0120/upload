<?php

namespace Recca0120\Upload\Tests;

use Mockery as m;
use Recca0120\Upload\Dropzone;
use PHPUnit\Framework\TestCase;

class DropzoneTest extends TestCase
{
    protected function tearDown()
    {
        parent::tearDown();
        m::close();
    }

    public function testReceiveSingleFile()
    {
        $request = m::mock('Illuminate\Http\Request');
        $request->shouldReceive('root')->once()->andReturn($root = 'root');
        $api = new Dropzone(
            $config = ['chunks' => $chunksPath = 'foo/', 'storage' => $storagePath = 'foo/'],
            $request,
            $files = m::mock('Recca0120\Upload\Filesystem'),
            $chunkFileFactory = m::mock('Recca0120\Upload\ChunkFileFactory')
        );
        $inputName = 'dzfile';
        $request->shouldReceive('file')->once()->with($inputName)->andReturn(
            $uploadedFile = m::mock('Symfony\Component\HttpFoundation\File\UploadedFile')
        );
        $request->shouldReceive('has')->once()->with('dztotalchunkcount')->andReturn(false);

        $this->assertSame($uploadedFile, $api->receive($inputName));
    }

    public function testReceiveChunkedFile()
    {
        $request = m::mock('Illuminate\Http\Request');
        $request->shouldReceive('root')->once()->andReturn($root = 'root');
        $api = new Dropzone(
            $config = ['chunks' => $chunksPath = 'foo/', 'storage' => $storagePath = 'foo/'],
            $request,
            $files = m::mock('Recca0120\Upload\Filesystem'),
            $chunkFileFactory = m::mock('Recca0120\Upload\ChunkFileFactory')
        );
        $files->shouldReceive('isDirectory')->twice()->andReturn(true);
        $inputName = 'dzfile';
        $request->shouldReceive('file')->once()->with($inputName)->andReturn(
            $uploadedFile = m::mock('Symfony\Component\HttpFoundation\File\UploadedFile')
        );
        $uploadedFile->shouldReceive('getClientOriginalName')->once()->andReturn(
            $originalName = 'foo.php'
        );
        $request->shouldReceive('has')->once()->with('dztotalchunkcount')->andReturn(true);
        $request->shouldReceive('get')->once()->with('dztotalchunkcount', 1)->andReturn(
            $totalparts = '4'
        );
        $request->shouldReceive('get')->once()->with('dzchunkindex')->andReturn(
            $partindex = '3'
        );
        $request->shouldReceive('get')->once()->with('dzuuid')->andReturn(
            $uuid = 'foo.uuid'
        );
        $uploadedFile->shouldReceive('getRealPath')->once()->andReturn(
            $realPath = 'foo.realpath'
        );

        $chunkFileFactory->shouldReceive('create')->once()->with($originalName, $chunksPath, $storagePath, $uuid, null)->andReturn(
            $chunkFile = m::mock('Recca0120\Upload\ChunkFile')
        );

        $chunkFile->shouldReceive('appendFile')->once()->with($realPath, (int) $partindex)->andReturnSelf();
        $chunkFile->shouldReceive('createUploadedFile')->once()->with($totalparts)->andReturn(
            $uploadedFile
        );

        $this->assertSame($uploadedFile, $api->receive($inputName));
    }

    public function testReceiveChunkedFileWithParts()
    {
        $request = m::mock('Illuminate\Http\Request');
        $request->shouldReceive('root')->once()->andReturn($root = 'root');
        $api = new Dropzone(
            $config = ['chunks' => $chunksPath = 'foo/', 'storage' => $storagePath = 'foo/'],
            $request,
            $files = m::mock('Recca0120\Upload\Filesystem'),
            $chunkFileFactory = m::mock('Recca0120\Upload\ChunkFileFactory')
        );
        $files->shouldReceive('isDirectory')->twice()->andReturn(true);
        $inputName = 'qqfile';
        $request->shouldReceive('file')->once()->with($inputName)->andReturn(
            $uploadedFile = m::mock('Symfony\Component\HttpFoundation\File\UploadedFile')
        );
        $uploadedFile->shouldReceive('getClientOriginalName')->once()->andReturn(
            $originalName = 'foo.php'
        );
        $request->shouldReceive('has')->once()->with('dztotalchunkcount')->andReturn(true);
        $request->shouldReceive('get')->once()->with('dztotalchunkcount', 1)->andReturn(
            $totalparts = '4'
        );
        $request->shouldReceive('get')->once()->with('dzchunkindex')->andReturn(
            $partindex = '2'
        );
        $request->shouldReceive('get')->once()->with('dzuuid')->andReturn(
            $uuid = 'foo.uuid'
        );
        $uploadedFile->shouldReceive('getRealPath')->once()->andReturn(
            $realPath = 'foo.realpath'
        );

        $chunkFileFactory->shouldReceive('create')->once()->with($originalName, $chunksPath, $storagePath, $uuid, null)->andReturn(
            $chunkFile = m::mock('Recca0120\Upload\ChunkFile')
        );

        $chunkFile->shouldReceive('appendFile')->once()->with($realPath, (int) $partindex)->andReturnSelf();

        $chunkFile->shouldReceive('throwException')->once()->with([
            'success' => true,
            'uuid' => $uuid,
        ])->andReturn(
            $exception = m::mock('stdClass')
        );

        $this->assertSame($exception, $api->receive($inputName));
    }
}
