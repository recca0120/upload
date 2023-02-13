<?php

namespace Recca0120\Upload\Tests;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Mockery as m;
use PHPUnit\Framework\TestCase;
use Recca0120\Upload\ChunkFile;
use Recca0120\Upload\ChunkFileFactory;
use Recca0120\Upload\Filesystem;

class ChunkFileFactoryTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testCreate(): void
    {
        $chunkFileFactory = new ChunkFileFactory(m::mock(Filesystem::class));

        $chunkFile = $chunkFileFactory->create(
            'foo.php',
            'foo.chunksPath',
            'foo.storagePath',
            'foo.token'.'foo.mimeType'
        );

        $this->assertInstanceOf(ChunkFile::class, $chunkFile);
    }
}
