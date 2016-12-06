<?php

namespace Recca0120\Upload\Contracts;

use Illuminate\Http\Request;
use Recca0120\Upload\Filesystem;
use Symfony\Component\HttpFoundation\Response;
use Recca0120\Upload\Exceptions\ChunkedResponseException;

interface Uploader {

    /**
     * __construct.
     *
     * @param \Illuminate\Http\Request    $request
     * @param \Recca0120\Upload\Filesystem $filesystem
     * @param string    $path
     */
    public function __construct(Request $request, Filesystem $filesystem, $path = null);

    /**
     * setPath
     *
     * @param string $path
     *
     * @return static
     */
    public function setPath($path = null);

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
     * completedResponse.
     *
     * @method completedResponse
     *
     * @param \Symfony\Component\HttpFoundation\Response $response
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function completedResponse(Response $response);
}
