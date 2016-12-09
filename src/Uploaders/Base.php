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
     * TMPFILE_EXTENSION.
     *
     * @var string
     */
    const TMPFILE_EXTENSION = '.part';

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
     * $config.
     *
     * @var array
     */
    protected $config;

    /**
     * $path.
     *
     * @var string
     */
    protected $path;

    /**
     * __construct.
     *
     * @param array    $path
     * @param \Illuminate\Http\Request    $request
     * @param \Recca0120\Upload\Filesystem $filesystem
     */
    public function __construct($config = [], Request $request = null, Filesystem $filesystem = null)
    {
        $this->request = is_null($request) === true ? Request::capture() : $request;
        $this->filesystem = is_null($filesystem) === true ? new Filesystem : $filesystem;
        $this->config = $config;

        $path = isset($config['path']) === false ? sys_get_temp_dir().'/temp/' : $config['path'];
        $this->path = $path;
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
        $extension = static::TMPFILE_EXTENSION;
        $this->filesystem->appendStream($tmpfile.$extension, $input, $start);

        if ($isCompleted === false) {
            throw new ChunkedResponseException($headers);
        }

        $this->filesystem->move($tmpfile.$extension, $tmpfile);
        $size = $this->filesystem->size($tmpfile);

        return $this->filesystem->createUploadedFile($tmpfile, $originalName, $mimeType, $size);
    }

    /**
     * receive.
     *
     * @param string $name
     *
     * @throws ChunkedResponseException
     *
     * @return \Symfony\Component\HttpFoundation\File\UploadedFile
     */
    public function receive($name)
    {
        $this->makeDirectory();
        $uploadedFile = $this->doReceive($name);
        $this->cleanDirectory();

        return $uploadedFile;
    }

    /**
     * doReceive.
     *
     * @param string $name
     *
     * @throws ChunkedResponseException
     *
     * @return \Symfony\Component\HttpFoundation\File\UploadedFile
     */
    abstract protected function doReceive($name);

    /**
     * makeDirectory.
     */
    protected function makeDirectory()
    {
        if ($this->filesystem->isDirectory($this->path) === false) {
            $this->filesystem->makeDirectory($this->path, 0777, true, true);
        }
    }

    /**
     * cleanDirectory.
     */
    protected function cleanDirectory()
    {
        $time = time();
        $maxFileAge = 3600;
        $files = $this->filesystem->files($this->path);
        foreach ((array) $files as $file) {
            if ($this->filesystem->exists($file) === true && $this->filesystem->lastModified($file) < ($time - $maxFileAge)) {
                $this->filesystem->delete($file);
            }
        }
    }

    public function getFilesystem()
    {
        return $this->filesystem;
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
