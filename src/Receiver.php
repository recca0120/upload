<?php

namespace Recca0120\Upload;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Arr;
use Recca0120\Upload\Contracts\Api as ApiContract;
use Recca0120\Upload\Exceptions\ChunkedResponseException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Response;

class Receiver
{
    /**
     * @var ApiContract
     */
    protected $api;

    public function __construct(ApiContract $api)
    {
        $this->api = $api;
    }

    public function receive(string $inputName = 'file', callable $callback = null): Response
    {
        try {
            $callback = $callback ?: [$this, 'callback'];
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

    public static function factory(array $config = [], string $class = FileAPI::class): Receiver
    {
        $lookup = [
            'dropzone' => Dropzone::class,
            'fileapi' => FileAPI::class,
            'fineuploader' => FineUploader::class,
            'plupload' => Plupload::class,
        ];

        $class = Arr::get($lookup, strtolower($class), $class);

        return new static(new $class($config));
    }

    protected function callback(UploadedFile $uploadedFile, $path, $domain): JsonResponse
    {
        $clientPathInfo = $this->pathInfo($uploadedFile->getClientOriginalName());
        $basePathInfo = $this->pathInfo($uploadedFile->getBasename());
        $filename = md5($basePathInfo['basename']).'.'.$clientPathInfo['extension'];
        $uploadedFile->move($path, $filename);

        return new JsonResponse([
            'name' => $clientPathInfo['filename'].'.'.$clientPathInfo['extension'],
            'tmp_name' => $path.$filename,
            'type' => $uploadedFile->getMimeType(),
            'size' => $uploadedFile->getSize(),
            'url' => $domain.$path.$filename,
        ]);
    }

    private function pathInfo($path): array
    {
        $parts = [];
        $parts['dirname'] = rtrim(substr($path, 0, strrpos($path, '/')), '/').'/';
        $parts['basename'] = ltrim(substr($path, strrpos($path, '/')), '/');
        $parts['extension'] = strtolower(substr(strrchr($path, '.'), 1));
        $parts['filename'] = ltrim(substr($parts['basename'], 0, strrpos($parts['basename'], '.')), '/');

        return $parts;
    }
}
