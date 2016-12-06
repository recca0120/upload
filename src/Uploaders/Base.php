<?php

namespace Recca0120\Upload\Uploaders;

use Illuminate\Http\Request;
use Recca0120\Upload\Filesystem;
use Recca0120\Upload\Contracts\Uploader;
use Symfony\Component\HttpFoundation\Response;
use Recca0120\Upload\Exceptions\ChunkedResponseException;

abstract class Base implements Uploader
{
    /**
     * $request.
     *
     * @var \Illuminate\Http\Request
     */
    protected $request;

    /**
     * $filesystem.
     *
     * @var \Recca0120\Upload\Filesystem
     */
    protected $filesystem;

    /**
     * $path.
     *
     * @var string
     */
    protected $path;

    /**
     * __construct.
     *
     * @param \Illuminate\Http\Request    $request
     * @param \Recca0120\Upload\Filesystem $filesystem
     * @param string    $path
     */
    public function __construct(Request $request, Filesystem $filesystem, $path = null)
    {
        $this->request = $request;
        $this->filesystem = $filesystem;
        $this->setPath($path);
    }

    /**
     * setPath.
     *
     * @param string $path
     *
     * @return static
     */
    public function setPath($path = null)
    {
        $this->path = is_null($path) === true ? sys_get_temp_dir() : $path;

        return $this;
    }

    /**
     * tmpfile.
     *
     * @param  string $originalName
     *
     * @return string
     */
    protected function tmpfile($originalName)
    {
        $extension = $this->filesystem->extension($originalName);
        $token = $this->request->get('token');

        return $this->path.'/'.md5($originalName.$token).'.'.$extension;
    }

    /**
     * receiveChunkedFile.
     *
     * @param  string|resource  $originalName
     * @param  string|resource  $input
     * @param  int  $start
     * @param  string  $mimeType
     * @param  bool $isCompleted
     * @param  array  $headers
     *
     * @return string
     */
    protected function receiveChunkedFile($originalName, $input, $start, $mimeType, $isCompleted = false, $headers = [])
    {
        $tmpfile = $this->tmpfile($originalName);
        $this->filesystem->appendStream($tmpfile.'.part', $input, $start);

        if ($isCompleted === false) {
            throw new ChunkedResponseException($headers);
        }

        $this->filesystem->move($tmpfile.'.part', $tmpfile);
        $size = $this->filesystem->size($tmpfile);

        return $this->filesystem->createUploadedFile($tmpfile, $originalName, $mimeType, $size);
    }

    /**
     * completedResponse.
     *
     * @method completedResponse
     *
     * @param \Symfony\Component\HttpFoundation\Response $response
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function completedResponse(Response $response)
    {
        return $response;
    }
}
