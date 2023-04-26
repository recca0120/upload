<?php

namespace Recca0120\Upload\Tests;

use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Mockery as m;
use PHPUnit\Framework\TestCase;
use Recca0120\Upload\ChunkFile;
use Recca0120\Upload\ChunkFileFactory;
use Recca0120\Upload\Exceptions\ChunkedResponseException;
use Recca0120\Upload\Exceptions\ResourceOpenException;
use Recca0120\Upload\Filesystem;
use Recca0120\Upload\Plupload;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class PluploadTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testReceiveUploadSingleFile(): void
    {
        $request = m::mock(Request::class);
        $request->allows('root')->once()->andReturn('root');
        $api = new Plupload(
            ['chunks' => $chunksPath = 'foo/', 'storage' => 'foo/'],
            $request,
            m::mock(Filesystem::class),
            m::mock(ChunkFileFactory::class)
        );
        $inputName = 'foo';
        $request->allows('file')->once()->with($inputName)->andReturn($uploadedFile = m::mock(UploadedFile::class));
        $request->allows('get')->once()->with('chunks')->andReturn('');

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
        $api = new Plupload(
            ['chunks' => $chunksPath = 'foo/', 'storage' => $storagePath = 'foo/'],
            $request,
            $files = m::mock(Filesystem::class),
            $chunkFileFactory = m::mock(ChunkFileFactory::class)
        );
        $files->allows('isDirectory')->twice()->andReturn(true);

        $inputName = 'foo';
        $request->allows('file')->once()->with($inputName)->andReturn($uploadedFile = m::mock(UploadedFile::class));
        $request->allows('get')->once()->with('chunks')->andReturn($chunks = 8);
        $request->allows('get')->once()->with('chunk')->andReturn($chunk = 8);
        $request->allows('get')->once()->with('name')->andReturn($originalName = 'foo.php');
        $uploadedFile->allows('getPathname')->once()->andReturn($pathname = 'foo');
        $request->allows('header')->once()->with('content-length')->andReturn($contentLength = 1049073);
        $request->allows('get')->once()->with('token')->andReturn($token = 'foo');

        $chunkFileFactory->allows('create')->once()->with($originalName, $chunksPath, $storagePath, $token,
            null)->andReturn($chunkFile = m::mock(ChunkFile::class));

        $chunkFile->allows('appendStream')->once()->with($pathname, $chunk * $contentLength)->andReturnSelf();
        $chunkFile->allows('createUploadedFile')->once()->andReturn($uploadedFile = m::mock(UploadedFile::class));

        $this->assertSame($uploadedFile, $api->receive($inputName));
    }

    /**
     * @throws FileNotFoundException
     * @throws ResourceOpenException
     */
    public function testReceiveChunkedFileAndThrowChunkedResponseException(): void
    {
        $this->expectException(ChunkedResponseException::class);
        $this->expectExceptionMessage('');

        $request = m::mock(Request::class);
        $request->allows('root')->once()->andReturn($root = 'root');
        $api = new Plupload(
            ['chunks' => $chunksPath = 'foo/', 'storage' => $storagePath = 'foo/'],
            $request,
            $files = m::mock(Filesystem::class),
            $chunkFileFactory = m::mock(ChunkFileFactory::class)
        );
        $files->allows('isDirectory')->twice()->andReturn(true);

        $inputName = 'foo';
        $request->allows('file')->once()->with($inputName)->andReturn($uploadedFile = m::mock(UploadedFile::class));
        $request->allows('get')->once()->with('chunks')->andReturn(8);
        $request->allows('get')->once()->with('chunk')->andReturn($chunk = 6);
        $request->allows('get')->once()->with('name')->andReturn($originalName = 'foo.php');
        $uploadedFile->allows('getPathname')->once()->andReturn($pathname = 'foo');
        $request->allows('header')->once()->with('content-length')->andReturn($contentLength = 1049073);

        $request->allows('get')->once()->with('token')->andReturn($token = 'foo');

        $chunkFileFactory->allows('create')
            ->once()
            ->with($originalName, $chunksPath, $storagePath, $token, null)
            ->andReturn($chunkFile = m::mock(ChunkFile::class));

        $chunkFile->allows('appendStream')->once()->with($pathname, $chunk * $contentLength)->andReturnSelf();

        $api->receive($inputName);
    }

    public function testCompletedResponse()
    {
        $request = m::mock(Request::class);
        $request->allows('root')->once()->andReturn('root');
        $api = new Plupload(
            ['chunks' => 'foo/', 'storage' => 'foo/'],
            $request,
            m::mock(Filesystem::class),
            m::mock(ChunkFileFactory::class)
        );
        $response = m::mock(JsonResponse::class);
        $response->allows('getData')->once()->andReturn($data = []);
        $response->allows('setData')->once()->with(['jsonrpc' => '2.0', 'result' => $data])->andReturnSelf();

        $this->assertSame($response, $api->completedResponse($response));
    }
}
