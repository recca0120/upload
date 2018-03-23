<?php

namespace Recca0120\Upload\Tests;

use Mockery as m;
use PHPUnit\Framework\TestCase;
use Recca0120\Upload\ChunkFileFactory;

class ChunkFileFactoryTest extends TestCase
{
    protected function tearDown()
    {
        parent::tearDown();
        m::close();
    }

    public function testCreate()
    {
        $chunkFileFactory = new ChunkFileFactory(
            $files = m::mock('Recca0120\Upload\Filesystem')
        );

        $chunkFile = $chunkFileFactory->create(
            $name = 'foo.php',
            $cunksPath = 'foo.chunksPath',
            $storagePath = 'foo.storagePath',
            $token = 'foo.token'.
            $mimeType = 'foo.mimeType'
        );
        $this->assertInstanceOf('Recca0120\Upload\ChunkFile', $chunkFile);
    }
}
