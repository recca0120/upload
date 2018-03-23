<?php

namespace Recca0120\Upload\Tests;

use Exception;
use Mockery as m;
use PHPUnit\Framework\TestCase;
use Recca0120\Upload\ChunkFile;
use Recca0120\Upload\Exceptions\ChunkedResponseException;

class ChunkFileTest extends TestCase
{
    protected function tearDown()
    {
        parent::tearDown();
        m::close();
    }

    public function testAppendStream()
    {
        $files = m::mock('Recca0120\Upload\Filesystem');

        $chunkFile = new ChunkFile(
            $name = __FILE__,
            $chunkPath = 'storage/chunk/',
            $storagePath = 'storage/',
            $token = uniqid(),
            $mimeType = 'text/plain',
            $files
        );

        $source = 'php://input';
        $offset = 0;
        $files->shouldReceive('tmpfilename')->once()->with($name, $token)->andReturn(
            $tmpfilename = 'foo.php'
        );
        $files->shouldReceive('appendStream')->once()->with($chunkPath.$tmpfilename.'.part', $source, $offset);

        try {
            $chunkFile->appendStream($source, $offset)->throwException();
        } catch (Exception $e) {
            $this->assertInstanceOf('Recca0120\Upload\Exceptions\ChunkedResponseException', $e);
        }
    }

    public function testAppendFile()
    {
        $files = m::mock('Recca0120\Upload\Filesystem');

        $chunkFile = new ChunkFile(
            $name = __FILE__,
            $chunkPath = 'storage/chunk/',
            $storagePath = 'storage/',
            $token = uniqid(),
            $mimeType = 'text/plain',
            $files
        );

        $source = 'php://input';
        $index = 0;
        $files->shouldReceive('tmpfilename')->once()->with($name, $token)->andReturn(
            $tmpfilename = 'foo.php'
        );
        $files->shouldReceive('appendStream')->once()->with($chunkPath.$tmpfilename.'.part.'.$index, $source, 0);

        try {
            $chunkFile->appendFile($source, $index)->throwException();
        } catch (Exception $e) {
            $this->assertInstanceOf('Recca0120\Upload\Exceptions\ChunkedResponseException', $e);
        }
    }

    public function testCreateUploadedFile()
    {
        $files = m::mock('Recca0120\Upload\Filesystem');
        $files->shouldReceive('mimeType')->once()->andReturn(
            $mimeType = 'text/plain'
        );

        $chunkFile = new ChunkFile(
            $name = __FILE__,
            $chunkPath = 'storage/chunk/',
            $storagePath = 'storage/',
            $token = uniqid(),
            null,
            $files
        );

        $source = 'php://input';
        $offset = 0;

        $files->shouldReceive('tmpfilename')->once()->with($name, $token)->andReturn(
            $tmpfilename = 'foo.php'
        );
        $files->shouldReceive('move')->once()->with($chunkPath.$tmpfilename.'.part', $storagePath.$tmpfilename);
        $files->shouldReceive('createUploadedFile')->once()->with(
            $storagePath.$tmpfilename, $name, $mimeType
        )->andReturn(
            $uploadedFile = m::mock('Symfony\Component\HttpFoundation\File\UploadedFile')
        );

        $this->assertSame($uploadedFile, $chunkFile->createUploadedFile());
    }

    public function testThrowException()
    {
        $files = m::mock('Recca0120\Upload\Filesystem');

        $chunkFile = new ChunkFile(
            $name = __FILE__,
            $chunkPath = 'storage/chunk/',
            $storagePath = 'storage/',
            $token = uniqid(),
            $mimeType = 'text/plain',
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
