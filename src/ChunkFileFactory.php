<?php

namespace Recca0120\Upload;

class ChunkFileFactory
{
    /**
     * @var Filesystem
     */
    private $files;

    public function __construct(Filesystem $files = null)
    {
        $this->files = $files ?: new Filesystem();
    }

    public function create(string $name, string $chunksPath, string $storagePath, string $token = null, string $mimeType = null): ChunkFile
    {
        return new ChunkFile($name, $chunksPath, $storagePath, $token, $mimeType, $this->files);
    }
}
