<?php

namespace Recca0120\Upload\Contracts;

use Illuminate\Http\Request;
use Recca0120\Upload\Filesystem;
use Symfony\Component\HttpFoundation\Response;
use Recca0120\Upload\Exceptions\ChunkedResponseException;

interface Uploader {
    public function __construct(Request $request, Filesystem $filesystem, $path = null);

    public function setPath($path = null);

    public function completedResponse(Response $response);

    /**
     * get.
     *
     * @param string $name
     *
     * @throws ChunkedResponseException
     *
     * @return \Symfony\Component\HttpFoundation\File\UploadedFile
     */
    public function get($name);
}
