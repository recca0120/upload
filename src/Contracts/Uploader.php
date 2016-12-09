<?php

namespace Recca0120\Upload\Contracts;

use Illuminate\Http\Request;
use Recca0120\Upload\Filesystem;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Recca0120\Upload\Exceptions\ChunkedResponseException;

interface Uploader
{
    /**
     * __construct.
     *
     * @param array    $config
     * @param \Illuminate\Http\Request    $request
     * @param \Recca0120\Upload\Filesystem $filesystem
     */
    public function __construct($config = [], Request $request = null, Filesystem $filesystem = null);

    /**
     * receive.
     *
     * @param string $name
     *
     * @throws ChunkedResponseException
     *
     * @return \Symfony\Component\HttpFoundation\File\UploadedFile
     */
    public function receive($name);

    /**
     * makeDirectory.
     *
     * @param string $path
     *
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
     * deleteUploadedFile.
     *
     * @param \Symfony\Component\HttpFoundation\File\UploadedFile
     */
    public function deleteUploadedFile(UploadedFile $uploadedFile);

    /**
     * completedResponse.
     *
     * @method completedResponse
     *
     * @param \Illuminate\Http\JsonResponse $response
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function completedResponse(JsonResponse $response);
}
