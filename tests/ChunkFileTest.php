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
        $chunkFile = new ChunkFile(
            $files = m::mock('Recca0120\Upload\Filesystem')
        );

        $chunkFile->setToken($token = uniqid())
            ->setName($name = __FILE__)
            ->setChunkPath($chunkPath = 'chunk/');

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

    public function testCreateUploadedFile()
    {
        $chunkFile = new ChunkFile(
            $files = m::mock('Recca0120\Upload\Filesystem')
        );

        $chunkFile
            ->setToken($token = uniqid())
            ->setName($name = __FILE__)
            ->setMimeType($mimeType = 'foo')
            ->setChunkPath($chunkPath = 'chunk/')
            ->setStoragePath($storagePath = 'chunk/');

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
        $chunkFile = new ChunkFile(
            $files = m::mock('Recca0120\Upload\Filesystem')
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
