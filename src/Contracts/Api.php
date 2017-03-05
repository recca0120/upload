<?php

namespace Recca0120\Upload\Contracts;

use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\File\UploadedFile;

interface Api
{
    /**
     * chunksPath.
     *
     * @return string
     */
    public function chunksPath();

    /**
     * storagePath.
     *
     * @return string
     */
    public function storagePath();

    /**
     * domain.
     *
     * @return string
     */
    public function domain();

    /**
     * path.
     *
     * @return string
     */
    public function path();

    /**
     * makeDirectory.
     *
     * @param string $path
     * @return static
     */
    public function makeDirectory($path);

    /**
     * cleanDirectory.
     *
     * @param string $path
     */
    public function cleanDirectory($path);

    /**
     * receive.
     *
     * @param string $name
     * @return \Symfony\Component\HttpFoundation\File\UploadedFile
     *
     * @throws \Recca0120\Upload\Exceptions\ChunkedResponseException
     */
    public function receive($name);

    /**
     * deleteUploadedFile.
     *
     * @param \Symfony\Component\HttpFoundation\File\UploadedFile
     */
    public function deleteUploadedFile(UploadedFile $uploadedFile);

    /**
     * completedResponse.
     *
     * @method completedResponse
     * @param \Illuminate\Http\JsonResponse $response
     * @return \Illuminate\Http\JsonResponse
     */
    public function completedResponse(JsonResponse $response);
}
