<?php

namespace Recca0120\Upload;

use Recca0120\Upload\Exceptions\ChunkedResponseException;

class ChunkFile
{
    /**
     * TMPFILE_EXTENSION.
     *
     * @var string
     */
    const TMPFILE_EXTENSION = '.part';

    /**
     * $token.
     *
     * @var string
     */
    protected $token = null;

    /**
     * $chunkPath.
     *
     * @var string
     */
    protected $chunkPath = null;

    /**
     * $storagePath.
     *
     * @var string
     */
    protected $storagePath = null;

    /**
     * $name.
     *
     * @var string
     */
    protected $name = null;

    /**
     * $mimeType.
     *
     * @var string
     */
    protected $mimeType = null;

    /**
     * $tmpfilename.
     *
     * @var string
     */
    protected $tmpfilename = null;

    /**
     * __construct.
     *
     * @param \Recca0120\Upload\Filesystem $filesystem
     */
    public function __construct(Filesystem $filesystem = null)
    {
        $this->filesystem = $filesystem ?: new Filesystem();
    }

    /**
     * setToken.
     *
     * @param string $token
     * @return $this
     */
    public function setToken($token)
    {
        $this->token = $token;

        return $this;
    }

    /**
     * setChunkPath.
     *
     * @param string $chunkPath
     * @return $this
     */
    public function setChunkPath($chunkPath)
    {
        $this->chunkPath = $chunkPath;

        return $this;
    }

    /**
     * setStoragePath.
     *
     * @param string $storagePath
     * @return $this
     */
    public function setStoragePath($storagePath)
    {
        $this->storagePath = $storagePath;

        return $this;
    }

    /**
     * setName.
     *
     * @param string $name
     * @return $this
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * setMimeType.
     *
     * @param string $name
     * @return $this
     */
    public function setMimeType($mimeType)
    {
        $this->mimeType = $mimeType;

        return $this;
    }

    /**
     * throwException.
     *
     * @param string $message
     * @param array $headers
     * @throws \Recca0120\Upload\Exceptions\ChunkedResponseException
     */
    public function throwException($message = '', $headers = [])
    {
        throw new ChunkedResponseException($message, $headers);
    }

    /**
     * appendStream.
     *
     * @param mixed $source
     * @param int $offset
     * @return $this
     */
    public function appendStream($source, $offset = 0)
    {
        $this->filesystem->appendStream($this->chunkFile(), $source, (int) $offset);

        return $this;
    }

    /**
     * createUploadedFile.
     *
     * @return \Illuminate\Http\UploadedFile
     */
    public function createUploadedFile()
    {
        $this->filesystem->move(
            $this->chunkFile(), $storageFile = $this->storageFile()
        );

        return $this->filesystem->createUploadedFile(
            $storageFile,
            $this->name,
            $this->mimeType
        );
    }

    /**
     * tmpfilename.
     *
     * @return string
     */
    protected function tmpfilename()
    {
        if (is_null($this->tmpfilename) === true) {
            $this->tmpfilename = $this->filesystem->tmpfilename($this->name, $this->token);
        }

        return $this->tmpfilename;
    }

    /**
     * chunkFile.
     *
     * @return string
     */
    protected function chunkFile()
    {
        return $this->chunkPath.$this->tmpfilename().static::TMPFILE_EXTENSION;
    }

    /**
     * storageFile.
     *
     * @return string
     */
    protected function storageFile()
    {
        return $this->storagePath.$this->tmpfilename();
    }
}
