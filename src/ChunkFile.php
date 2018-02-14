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
     * $files.
     *
     * @var \Recca0120\Upload\Filesystem
     */
    protected $files;

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
     * @param \Recca0120\Upload\Filesystem $files
     */
    public function __construct(Filesystem $files = null)
    {
        $this->files = $files ?: new Filesystem();
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
     * @param string $mimeType
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
     * @param mixed $message
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
        $chunkFile = $this->chunkFile();
        $this->files->appendStream($chunkFile, $source, (int) $offset);

        return $this;
    }

    /**
     * appendFile.
     *
     * @param mixed $source
     * @param int $index
     * @return $this
     */
    public function appendFile($source, $index = 0)
    {
        $chunkFile = $this->chunkFile().'.'.$index;
        $this->files->appendStream($chunkFile, $source, 0);

        return $this;
    }

    /**
     * createUploadedFile.
     *
     * @return \Illuminate\Http\UploadedFile
     */
    public function createUploadedFile($chunks = null, $storageFile = null)
    {
        $chunkFile = $this->chunkFile();
        $storageFile = $storageFile ?: $this->storageFile();

        if (is_null($chunks) === false) {
            for ($i = 0; $i < $chunks; $i++) {
                $chunk = $chunkFile.'.'.$i;
                $this->files->append(
                    $storageFile,
                    $this->files->get($chunk)
                );
                $this->files->delete($chunk);
            }
        } else {
            $this->files->move($chunkFile, $storageFile);
        }

        return $this->files->createUploadedFile(
            $storageFile, $this->name, $this->mimeType
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
            $this->tmpfilename = $this->files->tmpfilename($this->name, $this->token);
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
