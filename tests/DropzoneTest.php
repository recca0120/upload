<?php

namespace Recca0120\Upload\Tests;

use Exception;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Http\Request;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Mockery as m;
use PHPUnit\Framework\TestCase;
use Recca0120\Upload\ChunkFile;
use Recca0120\Upload\ChunkFileFactory;
use Recca0120\Upload\Dropzone;
use Recca0120\Upload\Exceptions\ResourceOpenException;
use Recca0120\Upload\Filesystem;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class DropzoneTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @throws FileNotFoundException
     * @throws ResourceOpenException
     */
    public function testReceiveSingleFile(): void
    {
        $request = m::mock(Request::class);
        $request->allows('root')->once()->andReturn('root');
        $api = new Dropzone(
            ['chunks' => 'foo/', 'storage' => 'foo/'],
            $request,
            m::mock(Filesystem::class),
            m::mock(ChunkFileFactory::class)
        );
        $inputName = 'dzfile';
        $request->allows('file')->once()->with($inputName)->andReturn($uploadedFile = m::mock(UploadedFile::class));
        $request->allows('has')->once()->with('dztotalchunkcount')->andReturn(false);

        $this->assertSame($uploadedFile, $api->receive($inputName));
    }

    /**
     * @throws FileNotFoundException
     * @throws ResourceOpenException
     */
    public function testReceiveChunkedFile(): void
    {
        $request = m::mock(Request::class);
        $request->allows('root')->once()->andReturn('root');
        $api = new Dropzone(
            ['chunks' => $chunksPath = 'foo/', 'storage' => $storagePath = 'foo/'],
            $request,
            $files = m::mock(Filesystem::class),
            $chunkFileFactory = m::mock(ChunkFileFactory::class)
        );
        $files->allows('isDirectory')->twice()->andReturn(true);
        $inputName = 'dzfile';
        $request->allows('file')->once()->with($inputName)->andReturn($uploadedFile = m::mock(UploadedFile::class));
        $uploadedFile->allows('getClientOriginalName')->once()->andReturn($originalName = 'foo.php');
        $request->allows('has')->once()->with('dztotalchunkcount')->andReturn(true);
        $request->allows('get')->once()->with('dztotalchunkcount', 1)->andReturn($totalparts = '4');
        $request->allows('get')->once()->with('dzchunkindex')->andReturn($partindex = '3');
        $request->allows('get')->once()->with('dzuuid')->andReturn($uuid = 'foo.uuid');
        $uploadedFile->allows('getRealPath')->once()->andReturn($realPath = 'foo.realpath');

        $chunkFileFactory->allows('create')->once()->with($originalName, $chunksPath, $storagePath, $uuid, null)->andReturn($chunkFile = m::mock(ChunkFile::class));
        $chunkFile->allows('appendFile')->once()->with($realPath, (int) $partindex)->andReturnSelf();
        $chunkFile->allows('createUploadedFile')->once()->with($totalparts)->andReturn($uploadedFile);

        $this->assertSame($uploadedFile, $api->receive($inputName));
    }

    /**
     * @throws FileNotFoundException
     * @throws ResourceOpenException
     */
    public function testReceiveChunkedFileWithParts()
    {
        $this->expectException(Exception::class);

        $request = m::mock(Request::class);
        $request->allows('root')->once()->andReturn('root');
        $api = new Dropzone(
            ['chunks' => $chunksPath = 'foo/', 'storage' => $storagePath = 'foo/'],
            $request,
            $files = m::mock(Filesystem::class),
            $chunkFileFactory = m::mock(ChunkFileFactory::class)
        );
        $files->allows('isDirectory')->twice()->andReturn(true);
        $inputName = 'qqfile';
        $request->allows('file')->once()->with($inputName)->andReturn($uploadedFile = m::mock(UploadedFile::class));
        $uploadedFile->allows('getClientOriginalName')->once()->andReturn($originalName = 'foo.php');
        $request->allows('has')->once()->with('dztotalchunkcount')->andReturn(true);
        $request->allows('get')->once()->with('dztotalchunkcount', 1)->andReturn('4');
        $request->allows('get')->once()->with('dzchunkindex')->andReturn($partindex = '2');
        $request->allows('get')->once()->with('dzuuid')->andReturn($uuid = 'foo.uuid');
        $uploadedFile->allows('getRealPath')->once()->andReturn($realPath = 'foo.realpath');

        $chunkFileFactory->allows('create')->once()->with($originalName, $chunksPath, $storagePath, $uuid, null)->andReturn($chunkFile = m::mock(ChunkFile::class));

        $chunkFile->allows('appendFile')->once()->with($realPath, (int) $partindex)->andReturnSelf();

        $chunkFile->allows('throwException')->once()->with(['success' => true, 'uuid' => $uuid])->andThrow($exception = new Exception());

        $this->assertSame($exception, $api->receive($inputName));
    }
}
