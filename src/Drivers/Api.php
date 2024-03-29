<?php

namespace Recca0120\Upload\Drivers;

use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Recca0120\Upload\ChunkFile;
use Recca0120\Upload\Contracts\Api as ApiContract;
use Recca0120\Upload\Exceptions\ResourceOpenException;
use Recca0120\Upload\Filesystem;

abstract class Api implements ApiContract
{
    /**
     * $request.
     *
     * @var Request
     */
    protected $request;

    /**
     * $files.
     *
     * @var Filesystem
     */
    protected $files;

    /**
     * $config.
     *
     * @var array
     */
    protected $config;

    public function __construct($config = [], Request $request = null, Filesystem $files = null)
    {
        $this->request = $request ?: Request::capture();
        $this->files = $files ?: new Filesystem();
        $this->config = array_merge([
            'chunks' => sys_get_temp_dir().'/chunks',
            'storage' => 'storage/temp',
            'domain' => $this->request->root(),
            'path' => 'storage/temp',
        ], $config);
    }

    public function domain(): string
    {
        return rtrim($this->config['domain'], '/').'/';
    }

    public function path(): string
    {
        return rtrim($this->config['path'], '/').'/';
    }

    public function makeDirectory(string $path): ApiContract
    {
        if ($this->files->isDirectory($path) === false) {
            $this->files->makeDirectory($path, 0777, true, true);
        }

        return $this;
    }

    public function cleanDirectory(string $path): ApiContract
    {
        $time = time();
        $maxFileAge = 3600;
        $files = $this->files->files($path);
        foreach ($files as $file) {
            if ($this->files->isFile($file) === true &&
                $this->files->lastModified($file) < ($time - $maxFileAge)
            ) {
                $this->files->delete($file);
            }
        }

        return $this;
    }

    /**
     * @throws ResourceOpenException
     * @throws FileNotFoundException
     */
    public function receive(string $name): UploadedFile
    {
        if ($this->isChunked($name)) {
            return $this->receiveChunked($name);
        }

        $uploadedFile = $this->request->file($name);
        $originalName = $uploadedFile->getClientOriginalName();
        $extension = $uploadedFile->getClientOriginalExtension();
        $mimeType = $uploadedFile->getMimeType();
        $target = md5($uploadedFile->getBasename()).'.'.$extension;
        $file = $uploadedFile->move($this->storagePath(), $target);

        return new UploadedFile($file->getPathname(), $originalName, $mimeType, null, true);
    }

    public function clearTempDirectories(): ApiContract
    {
        $this->cleanDirectory($this->chunkPath());
        $this->cleanDirectory($this->storagePath());

        return $this;
    }

    public function completedResponse(JsonResponse $response): JsonResponse
    {
        return $response;
    }

    protected function chunkPath(): string
    {
        $this->makeDirectory($this->config['chunks']);

        return rtrim($this->config['chunks'], '/').'/';
    }

    protected function storagePath(): string
    {
        $this->makeDirectory($this->config['storage']);

        return rtrim($this->config['storage'], '/').'/';
    }

    protected function createChunkFile(string $name, string $uuid = null, string $mimeType = null): ChunkFile
    {
        return new ChunkFile($this->files, $name, $this->chunkPath(), $this->storagePath(), $uuid, $mimeType);
    }

    abstract protected function isChunked(string $name): bool;

    abstract protected function isCompleted(string $name): bool;

    /**
     * @throws FileNotFoundException
     * @throws ResourceOpenException
     */
    abstract protected function receiveChunked(string $name): UploadedFile;
}
