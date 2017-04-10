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
    public $request;

    /**
     * $filesystem.
     *
     * @var \Recca0120\Upload\Filesystem
     */
    public $filesystem;

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
     * @param \Recca0120\Upload\Filesystem $filesystem
     */
    public function __construct($config = [], Request $request = null, Filesystem $filesystem = null, ChunkFile $chunkFile = null)
    {
        $this->request = $request ?: Request::capture();
        $this->filesystem = $filesystem ?: new Filesystem();
        $this->chunkFile = $chunkFile ?: new ChunkFile($this->filesystem);
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
        if ($this->filesystem->isDirectory($path) === false) {
            $this->filesystem->makeDirectory($path, 0777, true, true);
        }

        return $this;
    }

    /**
     * cleanDirectory.
     */
    public function cleanDirectory($path)
    {
        $time = time();
        $maxFileAge = 3600;
        $files = (array) $this->filesystem->files($path);
        foreach ($files as $file) {
            if ($this->filesystem->isFile($file) === true &&
                $this->filesystem->lastModified($file) < ($time - $maxFileAge)
            ) {
                $this->filesystem->delete($file);
            }
        }
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
        if ($this->filesystem->isFile($file) === true) {
            $this->filesystem->delete($file);
        }
        $this->cleanDirectory($this->config['chunks']);

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
}
