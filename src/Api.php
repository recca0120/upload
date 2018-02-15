<?php

namespace Recca0120\Upload;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Recca0120\Upload\Contracts\Api as ApiContract;
use Symfony\Component\HttpFoundation\File\UploadedFile;

abstract class Api implements ApiContract
{
    /**
     * $request.
     *
     * @var \Illuminate\Http\Request
     */
    protected $request;

    /**
     * $files.
     *
     * @var \Recca0120\Upload\Filesystem
     */
    protected $files;

    /**
     * $chunkFile.
     *
     * @var \Recca0120\Upload\ChunkFileFactory
     */
    protected $ChunkFileFactory;

    /**
     * $config.
     *
     * @var array
     */
    protected $config;

    /**
     * __construct.
     *
     * @param array $config
     * @param \Illuminate\Http\Request $request
     * @param \Recca0120\Upload\Filesystem $files
     * @param \Recca0120\Upload\ChunkFile $chunkFile
     */
    public function __construct($config = [], Request $request = null, Filesystem $files = null, ChunkFileFactory $chunkFileFactory = null)
    {
        $this->request = $request ?: Request::capture();
        $this->files = $files ?: new Filesystem();
        $this->chunkFileFactory = $chunkFileFactory ?: new ChunkFileFactory($this->files);
        $this->config = array_merge([
            'chunks' => sys_get_temp_dir().'/chunks',
            'storage' => 'storage/temp',
            'domain' => $this->request->root(),
            'path' => 'storage/temp',
        ], $config);
    }

    /**
     * domain.
     *
     * @return string
     */
    public function domain()
    {
        return rtrim($this->config['domain'], '/').'/';
    }

    /**
     * path.
     *
     * @return string
     */
    public function path()
    {
        return rtrim($this->config['path'], '/').'/';
    }

    /**
     * makeDirectory.
     *
     * @return $this
     */
    public function makeDirectory($path)
    {
        if ($this->files->isDirectory($path) === false) {
            $this->files->makeDirectory($path, 0777, true, true);
        }

        return $this;
    }

    /**
     * cleanDirectory.
     *
     * @param string $path
     * @return $this
     */
    public function cleanDirectory($path)
    {
        $time = time();
        $maxFileAge = 3600;
        $files = (array) $this->files->files($path);
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
     * receive.
     *
     * @param string $inputName
     * @return \Symfony\Component\HttpFoundation\File\UploadedFile
     *
     * @throws \Recca0120\Upload\Exceptions\ChunkedResponseException
     */
    abstract public function receive($inputName);

    /**
     * deleteUploadedFile.
     *
     * @param \Symfony\Component\HttpFoundation\File\UploadedFile
     * @return $this
     */
    public function deleteUploadedFile(UploadedFile $uploadedFile)
    {
        $file = $uploadedFile->getPathname();
        if ($this->files->isFile($file) === true) {
            $this->files->delete($file);
        }
        $this->cleanDirectory($this->chunkPath());

        return $this;
    }

    /**
     * completedResponse.
     *
     * @param \Illuminate\Http\JsonResponse $response
     * @return \Illuminate\Http\JsonResponse
     */
    public function completedResponse(JsonResponse $response)
    {
        return $response;
    }

    /**
     * chunkPath.
     *
     * @return string
     */
    protected function chunkPath()
    {
        $this->makeDirectory($this->config['chunks']);

        return rtrim($this->config['chunks'], '/').'/';
    }

    /**
     * storagePath.
     *
     * @return string
     */
    protected function storagePath()
    {
        $this->makeDirectory($this->config['storage']);

        return rtrim($this->config['storage'], '/').'/';
    }

    protected function createChunkFile($name, $uuid = null)
    {
        return $this->chunkFileFactory->create(
            $name, $this->chunkPath(), $this->storagePath(), $uuid
        );
    }
}
