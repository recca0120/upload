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
     * $url.
     *
     * @var string
     */
    protected $url;

    /**
     * __construct.
     *
     * @param \Recca0120\Upload\Contracts\Api $api
     */
    public function __construct(Api $api)
    {
        $this->api = $api;
    }

    /**
     * setUrl.
     *
     * @param string $url
     */
    public function setUrl($url)
    {
        $this->url = $url;

        return $this;
    }

    /**
     * receive.
     *
     * @param string  $inputName
     * @param Closure $callback
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function receive($inputName = 'file', Closure $callback = null)
    {
        try {
            $callback = $callback ?: $this->callback();
            $response = $callback(
                $uploadedFile = $this->api->receive($inputName),
                $this->api->path(),
                $this->api->domain(),
                $this->api
            );

            return $this->api->deleteUploadedFile($uploadedFile)->completedResponse($response);
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
        return function (UploadedFile $uploadedFile, $path, $domain) {
            $clientOriginalName = $uploadedFile->getClientOriginalName();
            $clientOriginalExtension = strtolower($uploadedFile->getClientOriginalExtension());
            $basename = pathinfo($uploadedFile->getBasename(), PATHINFO_FILENAME);
            $filename = $basename.'.'.$clientOriginalExtension;

            $response = [
                'name' => $clientOriginalName,
                'tmp_name' => $path.$filename,
                'type' => $uploadedFile->getMimeType(),
                'size' => $uploadedFile->getSize(),
                'url' => $domain.$path.$filename,
            ];

            return new JsonResponse($response);
        };
    }

    /**
     * factory.
     *
     * @param array  $config
     * @param string $class
     * @return \Recca0120\Upload\Contracts\Api
     */
    public static function factory($config = [], $class = FileAPI::class)
    {
        $class = Arr::get([
            'fileapi' => FileAPI::class,
            'plupload' => Plupload::class,
        ], strtolower($class), $class);

        return new static(new $class($config));
    }
}
