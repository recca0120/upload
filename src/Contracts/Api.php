<?php

namespace Recca0120\Upload\Contracts;

use Illuminate\Http\Request;
use Recca0120\Upload\Filesystem;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Recca0120\Upload\Exceptions\ChunkedResponseException;

interface Api
{
    /**
     * __construct.
     *
     * @param array                        $config
     * @param \Illuminate\Http\Request     $request
     * @param \Recca0120\Upload\Filesystem $filesystem
     */
    public function __construct($config = [], Request $request = null, Filesystem $filesystem = null);

    /**
     * setConfig.
     *
     * @param array $config
     *
     * @return static
     */
    public function setConfig($config);

    /**
     * setConfig.
     *
     * @param array $config
     *
     * @return static
     */
    public function getConfig();

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
