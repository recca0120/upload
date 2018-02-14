<?php

namespace Recca0120\Upload;

use Illuminate\Support\Arr;
use Illuminate\Http\JsonResponse;
use Recca0120\Upload\Contracts\Api as ApiContract;
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
     * __construct.
     *
     * @param \Recca0120\Upload\Contracts\Api $api
     */
    public function __construct(ApiContract $api)
    {
        $this->api = $api;
    }

    /**
     * receive.
     *
     * @param string $inputName
     * @param callable $callback
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function receive($inputName = 'file', callable $callback = null)
    {
        try {
            $callback = $callback ?: [$this, 'callback'];
            $response = call_user_func_array($callback, [
                $uploadedFile = $this->api->receive($inputName),
                $this->api->path(),
                $this->api->domain(),
                $this->api,
            ]);

            return $this->api->deleteUploadedFile($uploadedFile)->completedResponse($response);
        } catch (ChunkedResponseException $e) {
            return $e->getResponse();
        }
    }

    /**
     * factory.
     *
     * @param array $config
     * @param string $class
     * @return \Recca0120\Upload\Contracts\Api
     */
    public static function factory($config = [], $class = FileAPI::class)
    {
        $class = Arr::get([
            'fileapi' => FileAPI::class,
            'plupload' => Plupload::class,
            'fineuploader' => FineUploader::class,
        ], strtolower($class), $class);

        return new static(new $class($config));
    }

    /**
     * callback.
     *
     * @return \callable
     */
    protected function callback(UploadedFile $uploadedFile, $path, $domain)
    {
        $clientOriginalName = $uploadedFile->getClientOriginalName();
        $clientOriginalExtension = strtolower($uploadedFile->getClientOriginalExtension());
        $basename = pathinfo($uploadedFile->getBasename(), PATHINFO_FILENAME);
        $filename = md5($basename).'.'.$clientOriginalExtension;
        $mimeType = $uploadedFile->getMimeType();
        $size = $uploadedFile->getSize();
        $uploadedFile->move($path, $filename);
        $response = [
            'name' => pathinfo($clientOriginalName, PATHINFO_FILENAME).'.'.$clientOriginalExtension,
            'tmp_name' => $path.$filename,
            'type' => $mimeType,
            'size' => $size,
            'url' => $domain.$path.$filename,
        ];

        return new JsonResponse($response);
    }
}
