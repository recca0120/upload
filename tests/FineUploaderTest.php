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
            $chunkFile = m::mock('Recca0120\Upload\ChunkFile')
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
            $chunkFile = m::mock('Recca0120\Upload\ChunkFile')
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
            $qqtotalparts = '4'
        );
        $request->shouldReceive('get')->once()->with('qqpartindex')->andReturn(
            $qqpartindex = '3'
        );
        $request->shouldReceive('get')->once()->with('qquuid')->andReturn(
            $qquuid = 'foo.qquuid'
        );
        $uploadedFile->shouldReceive('getRealPath')->once()->andReturn(
            $realPath = 'foo.realpath'
        );

        $chunkFile->shouldReceive('setToken')->once()->with($qquuid)->andReturnSelf();
        $chunkFile->shouldReceive('setChunkPath')->once()->with($chunksPath)->andReturnSelf();
        $chunkFile->shouldReceive('setStoragePath')->once()->with($storagePath)->andReturnSelf();
        $chunkFile->shouldReceive('setName')->once()->with($originalName)->andReturnSelf();
        $chunkFile->shouldReceive('appendStream')->once()->with($realPath, 0, $qqpartindex);
        $chunkFile->shouldReceive('createUploadedFile')->once()->with($qqtotalparts)->andReturn(
            $uploadedFile
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
            $chunkFile = m::mock('Recca0120\Upload\ChunkFile')
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
            $qqtotalparts = '4'
        );
        $request->shouldReceive('get')->once()->with('qqpartindex')->andReturn(
            $qqpartindex = '2'
        );
        $request->shouldReceive('get')->once()->with('qquuid')->andReturn(
            $qquuid = 'foo.qquuid'
        );
        $uploadedFile->shouldReceive('getRealPath')->once()->andReturn(
            $realPath = 'foo.realpath'
        );

        $chunkFile->shouldReceive('setToken')->once()->with($qquuid)->andReturnSelf();
        $chunkFile->shouldReceive('setChunkPath')->once()->with($chunksPath)->andReturnSelf();
        $chunkFile->shouldReceive('setStoragePath')->once()->with($storagePath)->andReturnSelf();
        $chunkFile->shouldReceive('setName')->once()->with($originalName)->andReturnSelf();
        $chunkFile->shouldReceive('appendStream')->once()->with($realPath, 0, (int) $qqpartindex)->andReturnSelf();
        $chunkFile->shouldReceive('throwException')->once()->with([
            'success' => true,
            'uuid' => $qquuid,
        ])->andReturn(
            $exception = m::mock('stdClass')
        );

        $this->assertSame($exception, $api->receive($inputName));
    }
}
