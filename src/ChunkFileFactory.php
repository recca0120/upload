<?php

namespace Recca0120\Upload;

class ChunkFileFactory
{
    /**
     * __construct.
     *
     * @param \Recca0120\Upload\Filesystem $files
     */
    public function __construct(Filesystem $files = null)
    {
        $this->files = $files ?: new Filesystem();
    }

    /**
     * create.
     *
     * @return \Recca0120\Upload\ChunkFile
     */
    public function create($name, $chunksPath, $storagePath, $token = null, $mimeType = null)
    {
        return new ChunkFile($name, $chunksPath, $storagePath, $token, $mimeType, $this->files);
    }
}
