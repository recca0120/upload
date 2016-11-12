<?php

namespace Recca0120\Upload;

use Closure;
use Recca0120\Upload\Apis\Api;
use Symfony\Component\HttpFoundation\Response;

class ApiAdapter
{
    /**
     * $filesystem.
     *
     * @var \Recca0120\Upload\Filesystem
     */
    protected $filesystem;

    /**
     * $api.
     *
     * @var \Recca0120\Upload\Api
     */
    protected $api;

    /**
     * file age.
     *
     * @var int
     */
    protected $maxFileAge = 600;

    /**
     * __construct.
     *
     * @method __construct
     *
     * @param \Recca0120\Upload\Apis\Api                   $api
     * @param \Recca0120\Upload\Filesystem                 $filesystem
     * @param \Illuminate\Contracts\Foundation\Application $config
     */
    public function __construct(Api $api, Filesystem $filesystem, $config = [])
    {
        $this->api = $api;
        $this->filesystem = $filesystem;
        $this->config = $config;
    }

    /**
     * receive.
     *
     * @method receive
     *
     * @param string   $name
     * @param \Closure $closure
     *
     * @return \Symfony\Component\HttpFoundation\Response;
     */
    public function receive($name, Closure $closure)
    {
        $this->api->setName($name);
        if ($this->api->hasChunks() === false) {
            return $closure($this->api->getFile());
        }

        $filesystem = $this->getFilsystem();
        $path = $this->getPath();
        $resource = $this->api->getResource();
        $startOffset = $this->api->getStartOffset();
        $partialName = $path.$this->api->getPartialName();
        $filesystem->updateStream($partialName, $resource, [
            'startOffset' => $startOffset,
        ]);

        if ($this->api->isCompleted() === false) {
            return $this->api->chunkedResponse(new Response(null, 201));
        }

        $originalName = $this->api->getOriginalName();
        $mimeType = $this->api->getMimeType();
        $tmpName = substr($partialName, 0, -5);
        $filesystem->move($partialName, $tmpName);
        $uploadedFile = $this->createUploadedFile($tmpName, $originalName, $mimeType, $filesystem->size($tmpName));

        $response = $closure($uploadedFile);
        if ($filesystem->isFile($tmpName) === true) {
            $filesystem->delete($tmpName);
        }
        $response = $this->api->completedResponse($response);

        $this->removeOldData($path, $this->maxFileAge);

        return $response;
    }

    protected function createUploadedFile($path, $originalName, $mimeType = null, $size = null)
    {
        $class = class_exists('Illuminate\Http\UploadedFile') ?
            'Illuminate\Http\UploadedFile' :
            'Symfony\Component\HttpFoundation\File\UploadedFile';

        return new $class($path, $originalName, $mimeType, $size, UPLOAD_ERR_OK, true);
    }

    /**
     * removeOldData.
     *
     * @method removeOldData
     */
    public function removeOldData($path, $maxFileAge = 600)
    {
        $filesystem = $this->getFilsystem();
        $path = is_null($path) === true ? $this->getPath() : $path;
        $time = time();
        foreach ($filesystem->files($path) as $file) {
            if ($filesystem->exists($file) === true && $filesystem->lastModified($file) < ($time - $this->maxFileAge)) {
                $filesystem->delete($file);
            }
        }
    }

    /**
     * getPath.
     *
     * @method getPath
     *
     * @return string
     */
    public function getPath()
    {
        $filesystem = $this->getFilsystem();
        $diskPath = array_get($this->config, 'path');
        if ($filesystem->isDirectory($diskPath) === false) {
            $filesystem->makeDirectory($diskPath, 0777, true, true);
        }

        return $diskPath;
    }

    /**
     * getFilsystem.
     *
     * @method getFilsystem
     *
     * @return \Recca0120\Upload\Filesystem
     */
    public function getFilsystem()
    {
        return $this->filesystem;
    }
}
