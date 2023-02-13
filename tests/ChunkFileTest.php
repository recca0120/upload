<?php

namespace Recca0120\Upload\Tests;

use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Mockery as m;
use PHPUnit\Framework\TestCase;
use Recca0120\Upload\ChunkFile;
use Recca0120\Upload\Exceptions\ChunkedResponseException;
use Recca0120\Upload\Filesystem;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class ChunkFileTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testAppendStream(): void
    {
        $this->expectException(ChunkedResponseException::class);

        $files = m::mock(Filesystem::class);

        $chunkFile = new ChunkFile(
            $name = __FILE__,
            $chunkPath = 'storage/chunk/',
            'storage/',
            $token = uniqid('', true),
            'text/plain',
            $files
        );

        $source = 'php://input';
        $offset = 0;
        $files->allows('tmpfilename')->once()->with($name, $token)->andReturn($tmpfilename = 'foo.php');
        $files->allows('appendStream')->once()->with($chunkPath.$tmpfilename.'.part', $source, $offset);

        $chunkFile->appendStream($source, $offset)->throwException();
    }

    public function testAppendFile(): void
    {
        $this->expectException(ChunkedResponseException::class);

        $files = m::mock(Filesystem::class);

        $chunkFile = new ChunkFile(
            $name = __FILE__,
            $chunkPath = 'storage/chunk/',
            'storage/',
            $token = uniqid('', true),
            'text/plain',
            $files
        );

        $source = 'php://input';
        $index = 0;
        $files->allows('tmpfilename')->once()->with($name, $token)->andReturn($tmpfilename = 'foo.php');
        $files->allows('appendStream')->once()->with($chunkPath.$tmpfilename.'.part.'.$index, $source, 0);

        $chunkFile->appendFile($source, $index)->throwException();
    }

    /**
     * @throws FileNotFoundException
     */
    public function testCreateUploadedFile(): void
    {
        $files = m::mock(Filesystem::class);
        $files->allows('mimeType')->once()->andReturn($mimeType = 'text/plain');

        $chunkFile = new ChunkFile(
            $name = __FILE__,
            $chunkPath = 'storage/chunk/',
            $storagePath = 'storage/',
            $token = uniqid('', true),
            null,
            $files
        );

        $files->allows('tmpfilename')->once()->with($name, $token)->andReturn($tmpfilename = 'foo.php');
        $files->allows('move')->once()->with($chunkPath.$tmpfilename.'.part', $storagePath.$tmpfilename);
        $files->allows('createUploadedFile')->once()->with($storagePath.$tmpfilename, $name, $mimeType)
            ->andReturn($uploadedFile = m::mock(UploadedFile::class));

        $this->assertSame($uploadedFile, $chunkFile->createUploadedFile());
    }

    public function testThrowException(): void
    {
        $files = m::mock(Filesystem::class);

        $chunkFile = new ChunkFile(
            __FILE__,
            'storage/chunk/',
            'storage/',
            uniqid('', true),
            'text/plain',
            $files
        );

        try {
            $chunkFile->throwException(['foo' => 'bar'], ['foo' => 'bar']);
        } catch (ChunkedResponseException $e) {
            $response = $e->getResponse();
            $this->assertSame(201, $response->getStatusCode());
            $this->assertSame(json_encode(['foo' => 'bar']), $e->getMessage());
            $this->assertSame('bar', $response->headers->get('foo'));
        }
    }
}
