<?php

namespace Recca0120\Upload;

use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Http\UploadedFile;
use Recca0120\Upload\Exceptions\ResourceOpenException;

class ChunkFile
{
    public const TMPFILE_EXTENSION = '.part';

    /**
     * @var Filesystem
     */
    protected $files;

    /**
     * @var string|null
     */
    protected $token;

    /**
     * @var string|null
     */
    protected $chunkPath;

    /**
     * @var string|null
     */
    protected $storagePath;

    /**
     * @var string|null
     */
    protected $name;

    /**
     * @var string|null
     */
    protected $mimeType;

    /**
     * @var string|null
     */
    protected $tmpfilename;

    public function __construct(
        Filesystem $files,
        string $name,
        string $chunkPath,
        string $storagePath,
        string $token = null,
        string $mimeType = null
    ) {
        $this->files = $files;
        $this->name = $name;
        $this->chunkPath = $chunkPath;
        $this->storagePath = $storagePath;
        $this->token = $token;
        $this->mimeType = $mimeType;
    }

    /**
     * appendStream.
     *
     * @param  mixed  $source
     * @return $this
     *
     * @throws ResourceOpenException
     */
    public function appendStream($source, int $offset = 0): ChunkFile
    {
        $this->files->appendStream($this->chunkFile(), $source, $offset);

        return $this;
    }

    /**
     * appendFile.
     *
     * @param  mixed  $source
     * @return $this
     *
     * @throws ResourceOpenException
     */
    public function appendFile($source, int $index = 0): ChunkFile
    {
        $this->files->appendStream($this->chunkFile().'.'.$index, $source, 0);

        return $this;
    }

    /**
     * @throws FileNotFoundException
     */
    public function createUploadedFile($chunks = null): UploadedFile
    {
        $chunkFile = $this->chunkFile();
        $storageFile = $this->storageFile();

        if (is_null($chunks) === false) {
            for ($i = 0; $i < $chunks; $i++) {
                $chunk = $chunkFile.'.'.$i;
                $this->files->append($storageFile, $this->files->get($chunk));
                $this->files->delete($chunk);
            }
        } else {
            $this->files->move($chunkFile, $storageFile);
        }

        return $this->files->createUploadedFile($storageFile, $this->name, $this->files->mimeType($storageFile));
    }

    private function tmpfilename(): ?string
    {
        if (is_null($this->tmpfilename) === true) {
            $this->tmpfilename = $this->files->tmpfilename($this->name, $this->token);
        }

        return $this->tmpfilename;
    }

    private function chunkFile(): string
    {
        return $this->chunkPath.$this->tmpfilename().static::TMPFILE_EXTENSION;
    }

    private function storageFile(): string
    {
        return $this->storagePath.$this->tmpfilename();
    }
}
