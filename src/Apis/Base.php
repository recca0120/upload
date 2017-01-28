<?php

namespace Recca0120\Upload\Apis;

use Illuminate\Support\Arr;
use Illuminate\Http\Request;
use Recca0120\Upload\Filesystem;
use Illuminate\Http\JsonResponse;
use Recca0120\Upload\Contracts\Api;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Recca0120\Upload\Exceptions\ChunkedResponseException;

abstract class Base implements Api
{
    /**
     * TMPFILE_EXTENSION.
     *
     * @var string
     */
    const TMPFILE_EXTENSION = '.part';

    /**
     * $request.
     *
     * @var \Illuminate\Http\Request
     */
    protected $request;

    /**
     * $filesystem.
     *
     * @var \Recca0120\Upload\Filesystem
     */
    protected $filesystem;

    /**
     * $config.
     *
     * @var array
     */
    protected $config;

    /**
     * $chunksPath.
     *
     * @var string
     */
    protected $chunksPath;

    /**
     * __construct.
     *
     * @param array                        $config
     * @param \Illuminate\Http\Request     $request
     * @param \Recca0120\Upload\Filesystem $filesystem
     */
    public function __construct($config = [], Request $request = null, Filesystem $filesystem = null)
    {
        $this->request = $request ?: Request::capture();
        $this->filesystem = $filesystem ?: new Filesystem();
        $this->setConfig($config);
    }

    /**
     * getConfig.
     *
     * @return array
     */
    public function getConfig()
    {
        return $this->config;
    }

    /**
     * setConfig.
     *
     * @param array $config
     *
     * @return static
     */
    public function setConfig($config)
    {
        $this->config = $config;
        $this->chunksPath = Arr::get($config, 'chunks', sys_get_temp_dir().'/chunks');

        return $this;
    }

    /**
     * getChunksPath.
     *
     * @return string
     */
    public function getChunksPath()
    {
        return rtrim($this->chunksPath, '/').'/';
    }

    /**
     * makeDirectory.
     *
     * @return static
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
        $files = $this->filesystem->files($path);
        foreach ((array) $files as $file) {
            if ($this->filesystem->isFile($file) === true &&
                $this->filesystem->lastModified($file) < ($time - $maxFileAge)
            ) {
                $this->filesystem->delete($file);
            }
        }
    }

    /**
     * tmpfile.
     *
     * @param string $originalName
     *
     * @return string
     */
    protected function tmpfile($originalName)
    {
        $extension = $this->filesystem->extension($originalName);
        $token = $this->request->get('token');

        return $this->getChunksPath().md5($originalName.$token).'.'.$extension;
    }

    /**
     * receiveChunkedFile.
     *
     * @param string|resource $originalName
     * @param string|resource $input
     * @param int             $start
     * @param string          $mimeType
     * @param bool            $isCompleted
     * @param array           $headers
     *
     * @throws \Recca0120\Upload\Exceptions\ChunkedResponseException
     *
     * @return \Symfony\Component\HttpFoundation\File\UploadedFile
     */
    protected function receiveChunkedFile($originalName, $input, $start, $mimeType, $isCompleted = false, $headers = [])
    {
        $tmpfile = $this->tmpfile($originalName);
        $extension = static::TMPFILE_EXTENSION;
        $this->filesystem->appendStream($tmpfile.$extension, $input, $start);

        if ($isCompleted === false) {
            throw new ChunkedResponseException($headers);
        }

        $this->filesystem->move($tmpfile.$extension, $tmpfile);
        $size = $this->filesystem->size($tmpfile);

        return $this->filesystem->createUploadedFile($tmpfile, $originalName, $mimeType, $size);
    }

    /**
     * receive.
     *
     * @param string $inputName
     *
     * @throws \Recca0120\Upload\Exceptions\ChunkedResponseException
     *
     * @return \Symfony\Component\HttpFoundation\File\UploadedFile
     */
    public function receive($inputName)
    {
        $chunksPath = $this->getChunksPath();
        $uploadedFile = $this->makeDirectory($chunksPath)->doReceive($inputName);
        $this->cleanDirectory($chunksPath);

        return $uploadedFile;
    }

    /**
     * doReceive.
     *
     * @param string $inputName
     *
     * @throws \Recca0120\Upload\Exceptions\ChunkedResponseException
     *
     * @return \Symfony\Component\HttpFoundation\File\UploadedFile
     */
    abstract protected function doReceive($inputName);

    /**
     * deleteUploadedFile.
     *
     * @param \Symfony\Component\HttpFoundation\File\UploadedFile
     *
     * @return static
     */
    public function deleteUploadedFile(UploadedFile $uploadedFile)
    {
        $file = $uploadedFile->getPathname();
        if ($this->filesystem->isFile($file) === true) {
            $this->filesystem->delete($file);
        }

        return $this;
    }

    /**
     * completedResponse.
     *
     * @method completedResponse
     *
     * @param \Illuminate\Http\JsonResponse $response
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function completedResponse(JsonResponse $response)
    {
        return $response;
    }
}
