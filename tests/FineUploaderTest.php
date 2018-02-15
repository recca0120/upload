<?php

namespace Recca0120\Upload\Tests;

use Mockery as m;
use PHPUnit\Framework\TestCase;
use Recca0120\Upload\FineUploader;

class FineUploaderTest extends TestCase
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
        $api = new FineUploader(
            $config = ['chunks' => $chunksPath = 'foo/', 'storage' => $storagePath = 'foo/'],
            $request,
            $files = m::mock('Recca0120\Upload\Filesystem'),
            $chunkFileFactory = m::mock('Recca0120\Upload\ChunkFileFactory')
        );
        $inputName = 'qqfile';
        $request->shouldReceive('file')->once()->with($inputName)->andReturn(
            $uploadedFile = m::mock('Symfony\Component\HttpFoundation\File\UploadedFile')
        );
        $request->shouldReceive('has')->once()->with('qqtotalparts')->andReturn(false);

        $this->assertSame($uploadedFile, $api->receive($inputName));
    }

    public function testReceiveChunkedFile()
    {
        $request = m::mock('Illuminate\Http\Request');
        $request->shouldReceive('root')->once()->andReturn($root = 'root');
        $api = new FineUploader(
            $config = ['chunks' => $chunksPath = 'foo/', 'storage' => $storagePath = 'foo/'],
            $request,
            $files = m::mock('Recca0120\Upload\Filesystem'),
            $chunkFileFactory = m::mock('Recca0120\Upload\ChunkFileFactory')
        );
        $files->shouldReceive('isDirectory')->twice()->andReturn(true);
        $inputName = 'qqfile';
        $request->shouldReceive('file')->once()->with($inputName)->andReturn(null);
        $request->shouldReceive('has')->once()->with('qqtotalparts')->andReturn(true);
        $request->shouldReceive('get')->once()->with('qqfilename')->andReturn(
            $originalName = 'foo.php'
        );
        $request->shouldReceive('get')->once()->with('qqtotalparts', 1)->andReturn(
            $totalparts = '4'
        );
        $request->shouldReceive('get')->once()->with('qqpartindex')->andReturn(
            $partindex = '3'
        );
        $request->shouldReceive('get')->once()->with('qquuid')->andReturn(
            $uuid = 'foo.qquuid'
        );

        $chunkFileFactory->shouldReceive('create')->once()->with($originalName, $chunksPath, $storagePath, $uuid)->andReturn(
            $chunkFile = m::mock('Recca0120\Upload\ChunkFile')
        );

        $chunkFile->shouldReceive('createUploadedFile')->once()->with($totalparts)->andReturn(
            $uploadedFile = m::mock('Symfony\Component\HttpFoundation\File\UploadedFile')
        );

        $this->assertSame($uploadedFile, $api->receive($inputName));
    }

    public function testReceiveChunkedFileWithParts()
    {
        $request = m::mock('Illuminate\Http\Request');
        $request->shouldReceive('root')->once()->andReturn($root = 'root');
        $api = new FineUploader(
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
        $request->shouldReceive('has')->once()->with('qqtotalparts')->andReturn(true);
        $request->shouldReceive('get')->once()->with('qqfilename')->andReturn(
            $originalName = 'foo.php'
        );
        $request->shouldReceive('get')->once()->with('qqtotalparts', 1)->andReturn(
            $totalparts = '4'
        );
        $request->shouldReceive('get')->once()->with('qqpartindex')->andReturn(
            $partindex = '3'
        );
        $request->shouldReceive('get')->once()->with('qquuid')->andReturn(
            $uuid = 'foo.qquuid'
        );
        $uploadedFile->shouldReceive('getRealPath')->once()->andReturn(
            $realPath = 'foo.realpath'
        );

        $chunkFileFactory->shouldReceive('create')->once()->with($originalName, $chunksPath, $storagePath, $uuid)->andReturn(
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
