<?php

namespace Recca0120\Upload\Tests;

use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Http\UploadedFile;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Mockery as m;
use PHPUnit\Framework\TestCase;
use Recca0120\Upload\ChunkFile;
use Recca0120\Upload\Exceptions\ResourceOpenException;
use Recca0120\Upload\Filesystem;

class ChunkFileTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @throws ResourceOpenException
     */
    public function testAppendStream(): void
    {
        $files = m::mock(Filesystem::class);

        $chunkFile = new ChunkFile(
            $files,
            $name = __FILE__,
            $chunkPath = 'storage/chunk/',
            'storage/',
            $token = uniqid('', true),
            'text/plain'
        );

        $source = 'php://input';
        $offset = 0;
        $files->allows('tmpfilename')->once()->with($name, $token)->andReturn($tmpfilename = 'foo.php');
        $files->allows('appendStream')->once()->with($chunkPath.$tmpfilename.'.part', $source, $offset);
        $chunkFile->appendStream($source, $offset);
    }

    /**
     * @throws ResourceOpenException
     */
    public function testAppendFile(): void
    {
        $files = m::mock(Filesystem::class);

        $chunkFile = new ChunkFile(
            $files,
            $name = __FILE__,
            $chunkPath = 'storage/chunk/',
            'storage/',
            $token = uniqid('', true),
            'text/plain'
        );

        $source = 'php://input';
        $index = 0;
        $files->allows('tmpfilename')->once()->with($name, $token)->andReturn($tmpfilename = 'foo.php');
        $files->allows('appendStream')->once()->with($chunkPath.$tmpfilename.'.part.'.$index, $source, 0);
        $chunkFile->appendFile($source, $index);
    }

    /**
     * @throws FileNotFoundException
     */
    public function testCreateUploadedFile(): void
    {
        $files = m::mock(Filesystem::class);
        $files->allows('mimeType')->once()->andReturn($mimeType = 'text/plain');

        $chunkFile = new ChunkFile(
            $files,
            $name = __FILE__,
            $chunkPath = 'storage/chunk/',
            $storagePath = 'storage/',
            $token = uniqid('', true),
            null
        );

        $files->allows('tmpfilename')->once()->with($name, $token)->andReturn($tmpfilename = 'foo.php');
        $files->allows('move')->once()->with($chunkPath.$tmpfilename.'.part', $storagePath.$tmpfilename);
        $files->allows('createUploadedFile')
            ->once()
            ->with($storagePath.$tmpfilename, $name, $mimeType)
            ->andReturn($uploadedFile = m::mock(UploadedFile::class));

        $this->assertSame($uploadedFile, $chunkFile->createUploadedFile());
    }
}
