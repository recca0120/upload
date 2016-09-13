<?php

namespace Recca0120\Upload;

use Closure;
use Illuminate\Contracts\Foundation\Application;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Response;
use Recca0120\Upload\Apis\Api;

class ApiAdapter
{
    /**
     * $app.
     *
     * @var \Illuminate\Contracts\Foundation\Application
     */
    protected $app;

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
     * @param \Illuminate\Contracts\Foundation\Application $app
     */
    public function __construct(Api $api, Filesystem $filesystem, Application $app)
    {
        $this->api = $api;
        $this->filesystem = $filesystem;
        $this->app = $app;
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

        $resourceName = $this->api->getResourceName();
        $startOffset = $this->api->getStartOffset();
        $partialName = $this->api->getPartialName($this->getChunkPath());
        $this->filesystem->appendStream($resourceName, $partialName, $startOffset);

        if ($this->api->isCompleted() === false) {
            return $this->api->chunkedResponse(new Response(null, 201));
        }

        $originalName = $this->api->getOriginalName();
        $mimeType = $this->api->getMimeType();
        $tmpName = substr($partialName, 0, -5);
        $this->filesystem->move($partialName, $tmpName);
        $file = new UploadedFile($tmpName, $originalName, $mimeType, $this->filesystem->size($tmpName), UPLOAD_ERR_OK, true);

        $response = $closure($file);
        if ($this->filesystem->isFile($tmpName) === true) {
            $this->filesystem->delete($tmpName);
        }
        $response = $this->api->completedResponse($response);

        $this->removeOldData();

        return $response;
    }

    /**
     * getChunkPath.
     *
     * @method getChunkPath
     *
     * @return string
     */
    protected function getChunkPath()
    {
        $path = $this->app->storagePath().'/uploadchunks/';
        if ($this->filesystem->isDirectory($path) === false) {
            $this->filesystem->makeDirectory($path, 0755, true, true);
        }

        return $path;
    }

    /**
     * removeOldData.
     *
     * @method removeOldData
     */
    public function removeOldData($path = null, $maxFileAge = null)
    {
        $path = is_null($path) === true ? $this->getChunkPath() : $path;
        $maxFileAge = is_null($maxFileAge) === true ? $this->maxFileAge : $path;
        $time = time();
        foreach ($this->filesystem->files($path) as $file) {
            if ($this->filesystem->exists($file) === true && $this->filesystem->lastModified($file) < ($time - $this->maxFileAge)) {
                $this->filesystem->delete($file);
            }
        }
    }
}
