<?php

namespace Recca0120\Upload\Tests;

use Mockery as m;
use PHPUnit\Framework\TestCase;
use Recca0120\Upload\ChunkFile;
use Recca0120\Upload\Exceptions\ChunkedResponseException;

class ChunkFileTest extends TestCase
{
    protected function tearDown()
    {
        m::close();
    }

    public function testAppendStream()
    {
        $chunkFile = new ChunkFile(
            $filesystem = m::mock('Recca0120\Upload\Filesystem')
        );

        $chunkFile
            ->setToken($token = uniqid())
            ->setName($name = __FILE__)
            ->setChunkPath($chunkPath = 'chunk/');

        $source = 'php://input';
        $offset = 0;
        $filesystem->shouldReceive('tmpfilename')->once()->with($name, $token)->andReturn(
            $tmpfilename = 'foo.php'
        );
        $filesystem->shouldReceive('appendStream')->once()->with($chunkPath.$tmpfilename.'.part', $source, $offset);

        $chunkFile->appendStream($source, $offset);
    }

    public function testCreateUploadedFile()
    {
        $chunkFile = new ChunkFile(
            $filesystem = m::mock('Recca0120\Upload\Filesystem')
        );

        $chunkFile
            ->setToken($token = uniqid())
            ->setName($name = __FILE__)
            ->setMimeType($mimeType = 'foo')
            ->setChunkPath($chunkPath = 'chunk/')
            ->setStoragePath($storagePath = 'chunk/');

        $source = 'php://input';
        $offset = 0;

        $filesystem->shouldReceive('tmpfilename')->once()->with($name, $token)->andReturn(
            $tmpfilename = 'foo.php'
        );
        $filesystem->shouldReceive('move')->once()->with($chunkPath.$tmpfilename.'.part', $storagePath.$tmpfilename);
        $filesystem->shouldReceive('createUploadedFile')->once()->with(
            $storagePath.$tmpfilename, $name, $mimeType
        );

        $chunkFile->createUploadedFile();
    }

    public function testThrowException()
    {
        $chunkFile = new ChunkFile(
            $filesystem = m::mock('Recca0120\Upload\Filesystem')
        );

        try {
            $chunkFile->throwException('foo', ['foo' => 'bar']);
        } catch (ChunkedResponseException $e) {
            $response = $e->getResponse();
            $this->assertSame(201, $response->getStatusCode());
            $this->assertSame('foo', $e->getMessage());
            $this->assertSame('bar', $response->headers->get('foo'));
        }
    }
}
