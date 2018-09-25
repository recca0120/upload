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
            'dropzone' => Dropzone::class,
            'fileapi' => FileAPI::class,
            'fineuploader' => FineUploader::class,
            'plupload' => Plupload::class,
        ], strtolower($class), $class);

        return new static(new $class($config));
    }

    /**
     * callback.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    protected function callback(UploadedFile $uploadedFile, $path, $domain)
    {
        $clientPathInfo = $this->pathInfo($uploadedFile->getClientOriginalName());
        $basePathInfo = $this->pathInfo($uploadedFile->getBasename());
        $filename = md5($basePathInfo['basename']).'.'.$clientPathInfo['extension'];
        $mimeType = $uploadedFile->getMimeType();
        $size = $uploadedFile->getSize();
        $uploadedFile->move($path, $filename);
        $response = [
            'name' => $clientPathInfo['filename'].'.'.$clientPathInfo['extension'],
            'tmp_name' => $path.$filename,
            'type' => $mimeType,
            'size' => $size,
            'url' => $domain.$path.$filename,
        ];

        return new JsonResponse($response);
    }

    private function pathInfo($path)
    {
        $parts = [];
        $parts['dirname'] = rtrim(substr($path, 0, strrpos($path, '/')), '/').'/';
        $parts['basename'] = ltrim(substr($path, strrpos($path, '/')), '/');
        $parts['extension'] = strtolower(substr(strrchr($path, '.'), 1));
        $parts['filename'] = ltrim(substr($parts['basename'], 0, strrpos($parts ['basename'], '.')), '/');

        return $parts;
    }
}
