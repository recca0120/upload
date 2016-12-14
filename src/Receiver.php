<?php

namespace Recca0120\Upload;

use Closure;
use Illuminate\Support\Arr;
use Illuminate\Http\JsonResponse;
use Recca0120\Upload\Apis\FileAPI;
use Recca0120\Upload\Apis\Plupload;
use Recca0120\Upload\Contracts\Api;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Recca0120\Upload\Exceptions\ChunkedResponseException;

class Receiver
{
    /**
     * $api.
     *
     * @var \Recca0120\Upload\Contracts\Api
     */
    protected $api;

    /**
     * $basePath.
     *
     * @var string
     */
    public $basePath = null;

    /**
     * $baseUrl.
     *
     * @var string
     */
    public $baseUrl = null;

    /**
     * $storagePath.
     *
     * @var string
     */
    public $storagePath = null;

    /**
     * __construct.
     *
     * @param \Recca0120\Upload\Contracts\Api  $api
     */
    public function __construct(Api $api)
    {
        $config = $api->getConfig();
        $this->setBasePath(Arr::get($config, 'base_path', sys_get_temp_dir()));
        $this->setBaseUrl(Arr::get($config, 'base_url'));
        $this->setStoragePath(Arr::get($config, 'destination_url', 'storage/temp'));
        $this->api = $api;
    }

    /**
     * getBasePath.
     *
     * @return string
     */
    public function getBasePath()
    {
        return rtrim($this->basePath, '/').'/';
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
     * setStoragePath.
     *
     * @param string $storagePath
     *
     * @return static
     */
    public function setStoragePath($storagePath)
    {
        $this->storagePath = $storagePath;

        return $this;
    }

    /**
     * receive.
     *
     * @param  string $name
     * @param  Closure $closure
     * @param  string $storagePath
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function receive($inputName = 'file', Closure $callback = null, $storagePath = null)
    {
        $storagePath = is_null($storagePath) === true ? $this->storagePath : $storagePath;
        $callback = is_null($callback) === true ? $this->callback() : $callback;
        $absolutePath = $this->getBasePath().$storagePath;

        try {
            $uploadedFile = $this->api
                ->receive($inputName);

            $response = $callback($uploadedFile, $storagePath, $this->getBasePath(), $this->baseUrl, $this->api);

            return $this->api
                ->deleteUploadedFile($uploadedFile)
                ->completedResponse($response);
        } catch (ChunkedResponseException $e) {
            return $e->getResponse();
        }
    }

    /**
     * callback.
     *
     * @return \Closure
     */
    protected function callback()
    {
        return function (UploadedFile $uploadedFile, $storagePath, $basePath, $baseUrl, $api) {
            $api->makeDirectory($basePath.$storagePath);
            $clientOriginalName = $uploadedFile->getClientOriginalName();
            $clientOriginalExtension = strtolower($uploadedFile->getClientOriginalExtension());
            $basename = pathinfo($uploadedFile->getBasename(), PATHINFO_FILENAME);
            $filename = $basename.'.'.$clientOriginalExtension;
            $tempname = $storagePath.'/'.$filename;
            $mimeType = $uploadedFile->getMimeType();
            $size = $uploadedFile->getSize();

            $uploadedFile->move($basePath.$storagePath);

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

    /**
     * factory.
     *
     * @param  array $config
     * @param  string $class
     *
     * @return \Recca0120\Upload\Contracts\Api
     */
    public static function factory($config = [], $class = FileAPI::class)
    {
        $map = [
            'fileapi' => FileAPI::class,
            'plupload' => Plupload::class,
        ];

        $class = isset($map[strtolower($class)]) === true ? $map[$class] : $class;

        return new static(new $class($config));
    }
}
