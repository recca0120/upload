<?php

namespace Recca0120\Upload;

use Closure;
use Recca0120\Upload\Contracts\Uploader;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Recca0120\Upload\Exceptions\ChunkedResponseException;
use Symfony\Component\HttpFoundation\JsonResponse;

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

    public function save($name, $destination, $basePath = null, $baseUrl = null) {
        return $this->receive($name, function(UploadedFile $uploadedFile) use ($destination, $basePath, $baseUrl) {
            $clientOriginalName = $uploadedFile->getClientOriginalName();
            $extension = strtolower($uploadedFile->getClientOriginalExtension());
            $basename = pathinfo($uploadedFile->getBasename(), PATHINFO_FILENAME);
            $mimeType = $uploadedFile->getMimeType();
            $size = $uploadedFile->getSize();
            $filename = $basename.'.'.$extension;
            $uploadedFile->move($basePath.'/'.$destination, $filename);
            $tempname = $destination.'/'.$filename;

            $response = [
                'name' => $clientOriginalName,
                'tmp_name' => $tempname,
                'type' => $mimeType,
                'size' => $size,
            ];

            if (is_null($baseUrl) === false) {
                $response['url'] = rtrim($baseUrl, '/').'/'.$tempname;
            }

            return new JsonResponse($response);
        });
    }
}
