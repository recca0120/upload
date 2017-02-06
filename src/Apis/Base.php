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
     * receiveChunkedFile.
     *
     * @param string|resource $originalName
     * @param string|resource $input
     * @param int             $start
     * @param string          $mimeType
     * @param bool            $completed
     * @param array           $headers
     *
     * @throws \Recca0120\Upload\Exceptions\ChunkedResponseException
     *
     * @return \Symfony\Component\HttpFoundation\File\UploadedFile
     */
    protected function receiveChunkedFile($originalName, $input, $start, $mimeType, $completed = false, $headers = [])
    {
        $tmpfilename = $this->getChunksPath().$this->filesystem->tmpfilename(
            $originalName, $this->request->get('token')
        );
        $extension = static::TMPFILE_EXTENSION;
        $this->filesystem->appendStream($tmpfilename.$extension, $input, $start);

        if ($completed === false) {
            throw new ChunkedResponseException($headers);
        }

        $this->filesystem->move($tmpfilename.$extension, $tmpfilename);

        return $this->filesystem->createUploadedFile(
            $tmpfilename,
            $originalName,
            $mimeType,
            $this->filesystem->size($tmpfilename)
        );
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
