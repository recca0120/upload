<?php

namespace Recca0120\Upload\Tests;

use Exception;
use Illuminate\Http\Request;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Mockery as m;
use PHPUnit\Framework\TestCase;
use Recca0120\Upload\ChunkFile;
use Recca0120\Upload\ChunkFileFactory;
use Recca0120\Upload\Filesystem;
use Recca0120\Upload\FineUploader;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class FineUploaderTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testReceiveSingleFile(): void
    {
        $request = m::mock(Request::class);
        $request->allows('root')->once()->andReturn('root');

        $api = new FineUploader(
            ['chunks' => 'foo/', 'storage' => 'foo/'],
            $request,
            m::mock(Filesystem::class),
            m::mock(ChunkFileFactory::class)
        );

        $inputName = 'qqfile';
        $request->allows('file')->once()->with($inputName)->andReturn($uploadedFile = m::mock(UploadedFile::class));
        $request->allows('has')->once()->with('qqtotalparts')->andReturn(false);

        $this->assertSame($uploadedFile, $api->receive($inputName));
    }

    public function testReceiveChunkedFile(): void
    {
        $request = m::mock(Request::class);
        $request->allows('root')->once()->andReturn('root');

        $api = new FineUploader(
            ['chunks' => $chunksPath = 'foo/', 'storage' => $storagePath = 'foo/'],
            $request,
            $files = m::mock(Filesystem::class),
            $chunkFileFactory = m::mock(ChunkFileFactory::class)
        );
        $files->allows('isDirectory')->twice()->andReturn(true);
        $inputName = 'qqfile';
        $request->allows('file')->once()->with($inputName)->andReturn(null);
        $request->allows('has')->once()->with('qqtotalparts')->andReturn(true);
        $request->allows('get')->once()->with('qqfilename')->andReturn($originalName = 'foo.php');
        $request->allows('get')->once()->with('qqtotalparts', 1)->andReturn($totalparts = '4');
        $request->allows('get')->once()->with('qqpartindex')->andReturn('3');
        $request->allows('get')->once()->with('qquuid')->andReturn($uuid = 'foo.qquuid');

        $chunkFileFactory->allows('create')->once()->with($originalName, $chunksPath, $storagePath, $uuid, null)->andReturn($chunkFile = m::mock(ChunkFile::class));
        $chunkFile->allows('createUploadedFile')->once()->with($totalparts)->andReturn($uploadedFile = m::mock(UploadedFile::class));

        $this->assertSame($uploadedFile, $api->receive($inputName));
    }

    public function testReceiveChunkedFileWithParts(): void
    {
        $this->expectException(Exception::class);

        $request = m::mock(Request::class);
        $request->allows('root')->once()->andReturn('root');

        $api = new FineUploader(
            ['chunks' => $chunksPath = 'foo/', 'storage' => $storagePath = 'foo/'],
            $request,
            $files = m::mock(Filesystem::class),
            $chunkFileFactory = m::mock(ChunkFileFactory::class)
        );

        $files->allows('isDirectory')->twice()->andReturn(true);
        $inputName = 'qqfile';
        $request->allows('file')->once()->with($inputName)->andReturn($uploadedFile = m::mock(UploadedFile::class));
        $request->allows('has')->once()->with('qqtotalparts')->andReturn(true);
        $request->allows('get')->once()->with('qqfilename')->andReturn($originalName = 'foo.php');
        $request->allows('get')->once()->with('qqtotalparts', 1)->andReturn('4');
        $request->allows('get')->once()->with('qqpartindex')->andReturn($partindex = '3');
        $request->allows('get')->once()->with('qquuid')->andReturn($uuid = 'foo.qquuid');

        $uploadedFile->allows('getRealPath')->once()->andReturn($realPath = 'foo.realpath');

        $chunkFileFactory->allows('create')->once()->with($originalName, $chunksPath, $storagePath, $uuid, null)->andReturn($chunkFile = m::mock(ChunkFile::class));
        $chunkFile->allows('appendFile')->once()->with($realPath, (int) $partindex)->andReturnSelf();

        $chunkFile->allows('throwException')->once()->with(['success' => true, 'uuid' => $uuid])->andThrow(new Exception());

        $api->receive($inputName);
    }
}
