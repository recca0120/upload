<?php

namespace Recca0120\Upload;

use Closure;
use Illuminate\Support\Arr;
use Illuminate\Http\JsonResponse;
use Recca0120\Upload\Contracts\Uploader;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Recca0120\Upload\Exceptions\ChunkedResponseException;

class Receiver
{
    /**
     * $uploader.
     *
     * @var \Recca0120\Upload\Contracts\Uploader
     */
    protected $uploader;

    public $basePath = null;

    public $baseUrl = null;

    /**
     * __construct.
     *
     * @param \Recca0120\Upload\Contracts\Uploader  $uploader
     * @param array                                 $config
     */
    public function __construct(Uploader $uploader, $config = [])
    {
        $this->uploader = $uploader;
        $this->setBasePath(Arr::get($config, 'base_path'));
        $this->setBaseUrl(Arr::get($config, 'base_url'));
    }

    /**
     * setBasePath.
     *
     * @param string $basePath
     */
    public function setBasePath($basePath)
    {
        $this->basePath = $basePath;

        return $this;
    }

    /**
     * getBasePath.
     *
     * @return string
     */
    public function getBasePath()
    {
        return is_null($this->basePath) === true ? sys_get_temp_dir() : $this->basePath;
    }

    /**
     * setBaseUrl.
     *
     * @param string $baseUrl
     */
    public function setBaseUrl($baseUrl)
    {
        $this->baseUrl = $baseUrl;

        return $this;
    }

    /**
     * receive.
     *
     * @param  string $name
     * @param  Closure $closure
     * @param  string $destination
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function receive($name = 'file', Closure $callback = null, $destination = 'storage/temp')
    {
        $callback = is_null($callback) === true ? $this->callback() : $callback;
        $path = $this->getBasePath().'/'.$destination;

        try {
            $uploadedFile = $this->uploader
                ->makeDirectory($path)
                ->receive($name);

            $response = $callback($uploadedFile, $destination, $path, $this->baseUrl);

            return $this->uploader
                ->deleteUploadedFile($uploadedFile)
                ->completedResponse($response);
        } catch (ChunkedResponseException $e) {
            return $e->getResponse();
        }
    }

    /**
     * save.
     *
     * @param  string $name
     * @param  string $destination
     *
     * @return Closure
     */
    public function save($name, $destination)
    {
        return $this->receive($name, null, $destination);
    }

    /**
     * callback.
     *
     * @return \Closure
     */
    protected function callback()
    {
        return function (UploadedFile $uploadedFile, $destination, $path, $baseUrl) {
            $clientOriginalName = $uploadedFile->getClientOriginalName();
            $clientOriginalExtension = strtolower($uploadedFile->getClientOriginalExtension());
            $basename = pathinfo($uploadedFile->getBasename(), PATHINFO_FILENAME);
            $filename = $basename.'.'.$clientOriginalExtension;
            $tempname = $destination.'/'.$filename;
            $mimeType = $uploadedFile->getMimeType();
            $size = $uploadedFile->getSize();

            $uploadedFile->move($path, $filename);

            return $this->makeJsonResponse([
                'name' => $clientOriginalName,
                'tmp_name' => $tempname,
                'type' => $mimeType,
                'size' => $size,
            ], $baseUrl);
        };
    }

    /**
     * makeJsonResponse.
     *
     * @param array $data
     * @param string $baseUrl
     *
     * @return \Illuminate\Http\JsonResponse
     */
    protected function makeJsonResponse($data, $baseUrl = null)
    {
        if (is_null($baseUrl) === false) {
            $data['url'] = rtrim($baseUrl, '/').'/'.$data['tmp_name'];
        }

        return new JsonResponse($data);
    }
}
