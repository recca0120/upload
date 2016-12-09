<?php

namespace Recca0120\Upload;

use Closure;
use Recca0120\Upload\Contracts\Uploader;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Recca0120\Upload\Exceptions\ChunkedResponseException;
use Illuminate\Http\JsonResponse;

class Receiver
{
    /**
     * $uploader.
     *
     * @var \Recca0120\Upload\Contracts\Uploader
     */
    protected $uploader;

    /**
     * __construct.
     *
     * @param \Recca0120\Upload\Contracts\Uploader   $uploader
     */
    public function __construct(Uploader $uploader)
    {
        $this->uploader = $uploader;
    }

    /**
     * receive.
     *
     * @param  string $name
     * @param  Closure $closure
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function receive($name, Closure $closure)
    {
        try {
            $uploadedFile = $this->uploader->receive($name);
            $response = $closure($uploadedFile);

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
     * @param  Closure $closure
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function save($name, $destination, $basePath = null, $baseUrl = null) {
        $path = $basePath.'/'.$destination;
        $this->uploader->makeDirectory($path);

        return $this->receive($name, function(UploadedFile $uploadedFile) use ($destination, $path, $baseUrl) {
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
        });
    }

    /**
     * makeJsonResponse.
     *
     * @param array $data
     * @param string $baseUrl
     *
     * @return \Illuminate\Http\JsonResponse
     */
    protected function makeJsonResponse($data, $baseUrl = null) {
        if (is_null($baseUrl) === false) {
            $data['url'] = rtrim($baseUrl, '/').'/'.$data['tmp_name'];
        }

        return new JsonResponse($data);
    }
}
