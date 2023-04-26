<?php

namespace Recca0120\Upload\Tests;

use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Http\Request;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Mockery as m;
use PHPUnit\Framework\TestCase;
use Recca0120\Upload\ChunkFile;
use Recca0120\Upload\ChunkFileFactory;
use Recca0120\Upload\Exceptions\ChunkedResponseException;
use Recca0120\Upload\Exceptions\ResourceOpenException;
use Recca0120\Upload\FileAPI;
use Recca0120\Upload\Filesystem;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class FileAPITest extends TestCase
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
        $api = new FileAPI(
            ['chunks' => 'foo/', 'storage' => 'foo/'],
            $request,
            m::mock(Filesystem::class),
            m::mock(ChunkFileFactory::class)
        );
        $request->allows('header')->once()->with('content-disposition')->andReturn('');
        $inputName = 'foo';
        $request->allows('file')->once()->with($inputName)->andReturn($uploadedFile = m::mock(UploadedFile::class));
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
        $api = new FileAPI(
            ['chunks' => $chunksPath = 'foo/', 'storage' => $storagePath = 'foo/'],
            $request,
            $files = m::mock(Filesystem::class),
            $chunkFileFactory = m::mock(ChunkFileFactory::class)
        );
        $files->allows('isDirectory')->twice()->andReturn(true);

        $start = 5242880;
        $end = 7845180;
        $total = 7845180;
        $request->allows('get')->once()->with('name')->andReturn('');
        $request->allows('header')->once()->with('content-disposition')->andReturn(
            'attachment; filename="'.($originalName = 'foo.php').'"'
        );
        $request->allows('header')->once()->with('content-range')->andReturn('bytes '.$start.'-'.$end.'/'.$total);
        $request->allows('header')->once()->with('content-type')->andReturn($mimeType = 'foo');
        $request->allows('get')->once()->with('token')->andReturn($token = 'foo');

        $chunkFileFactory->allows('create')->once()->with($originalName, $chunksPath, $storagePath, $mimeType,
            $token)->andReturn(
            $chunkFile = m::mock(ChunkFile::class)
        );

        $chunkFile->allows('appendStream')->once()->with('php://input', $start)->andReturnSelf();
        $chunkFile->allows('createUploadedFile')->once()->andReturn($uploadedFile = m::mock(UploadedFile::class));

        $this->assertSame($uploadedFile, $api->receive('foo'));
    }

    public function testReceiveChunkedFileWithoutContentRange(): void
    {
        $request = m::mock(Request::class);
        $request->allows('root')->once()->andReturn($root = 'root');
        $api = new FileAPI(
            ['chunks' => $chunksPath = 'foo/', 'storage' => $storagePath = 'foo/'],
            $request,
            $files = m::mock(Filesystem::class),
            $chunkFileFactory = m::mock(ChunkFileFactory::class)
        );
        $files->allows('isDirectory')->twice()->andReturn(true);

        $request->allows('header')->once()->with('content-range')->andReturn(null);
        $request->allows('header')->once()->with('content-length')->andReturn(7845180);
        $request->allows('get')->once()->with('name')->andReturn('');
        $request->allows('header')->once()->with('content-disposition')->andReturn(
            'attachment; filename="'.($originalName = 'foo.php').'"'
        );
        $request->allows('header')->once()->with('content-type')->andReturn($mimeType = 'foo');
        $request->allows('get')->once()->with('token')->andReturn($token = 'foo');

        $chunkFileFactory->allows('create')->once()->with($originalName, $chunksPath, $storagePath, $mimeType,
            $token)->andReturn(
            $chunkFile = m::mock(ChunkFile::class)
        );

        $chunkFile->allows('appendStream')->once()->with('php://input', 0)->andReturnSelf();
        $chunkFile->allows('createUploadedFile')->once()->andReturn(
            $uploadedFile = m::mock(UploadedFile::class)
        );

        $this->assertSame($uploadedFile, $api->receive($inputName = 'foo'));
    }

    public function testReceiveChunkedFileAndThrowChunkedResponseException(): void
    {
        $this->expectException(ChunkedResponseException::class);
        $this->expectExceptionMessage('{"files":{"name":"foo.php","size":5767167,"type":"foo.mimeType"}}');

        $request = m::mock(Request::class);
        $request->allows('root')->once()->andReturn('root');
        $api = new FileAPI(
            ['chunks' => $chunksPath = 'foo/', 'storage' => $storagePath = 'foo/'],
            $request,
            $files = m::mock(Filesystem::class),
            $chunkFileFactory = m::mock(ChunkFileFactory::class)
        );
        $files->allows('isDirectory')->twice()->andReturn(true);

        $start = 5242880;
        $end = 5767167;
        $total = 7845180;
        $request->allows('header')->once()->with('content-range')->andReturn(
            'bytes '.$start.'-'.$end.'/'.$total
        );
        $request->allows('get')->once()->with('name')->andReturn('');
        $request->allows('header')->once()->with('content-disposition')->andReturn(
            'attachment; filename='.($originalName = 'foo.php')
        );
        $request->allows('header')->once()->with('content-type')->andReturn(
            $mimeType = 'foo'
        );

        $request->allows('get')->once()->with('token')->andReturn($token = 'foo');

        $chunkFileFactory
            ->allows('create')
            ->once()
            ->with($originalName, $chunksPath, $storagePath, $mimeType, $token)
            ->andReturn($chunkFile = m::mock(ChunkFile::class));

        $chunkFile->allows('appendStream')->once()->with('php://input', $start)->andReturnSelf();
        $chunkFile->allows('getMimeType')->andReturn($mimeType = 'foo.mimeType');

        $api->receive('foo');
    }
}
