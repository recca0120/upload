<?php

namespace Recca0120\Upload;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Recca0120\Upload\Contracts\Api as ApiContract;
use Recca0120\Upload\Exceptions\ChunkedResponseException;
use Symfony\Component\HttpFoundation\Response;

class Receiver
{
    /**
     * @var ApiContract
     */
    private $api;

    private static $lookup = [
        'dropzone' => Dropzone::class,
        'fileapi' => FileAPI::class,
        'fineuploader' => FineUploader::class,
        'plupload' => Plupload::class,
        'filepond' => FilePond::class,
    ];

    public function __construct(ApiContract $api)
    {
        $this->api = $api;
    }

    public function receive(string $inputName = 'file', callable $callback = null): Response
    {
        try {
            $callback = $callback ?: [$this, 'callback'];
            $response = $callback(
                $this->api->receive($inputName),
                $this->api->path(),
                $this->api->domain(),
                $this->api
            );

            return $this->api->clearTempDirectories()->completedResponse($response);
        } catch (ChunkedResponseException $e) {
            return $e->getResponse();
        }
    }

    public static function factory(array $config = [], string $class = FileAPI::class): Receiver
    {
        $class = Arr::get(self::$lookup, strtolower($class), $class);

        return new static(new $class($config));
    }

    protected function callback(UploadedFile $uploadedFile, $path, $domain): JsonResponse
    {
        $filename = $uploadedFile->getBasename();

        return new JsonResponse([
            'name' => $uploadedFile->getClientOriginalName(),
            'tmp_name' => $path.$filename,
            'type' => $uploadedFile->getMimeType(),
            'size' => $uploadedFile->getSize(),
            'url' => $domain.$path.$filename,
        ]);
    }
}
