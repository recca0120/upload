<?php

namespace Recca0120\Upload;

use Illuminate\Http\Request;
use Recca0120\Upload\Filesystem;
use Illuminate\Http\JsonResponse;
use Recca0120\Upload\Contracts\Api as ApiContract;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Recca0120\Upload\Exceptions\ChunkedResponseException;

abstract class Api implements ApiContract
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
     * __construct.
     *
     * @param array $config
     * @param \Illuminate\Http\Request $request
     * @param \Recca0120\Upload\Filesystem $filesystem
     */
    public function __construct($config = [], Request $request = null, Filesystem $filesystem = null)
    {
        $this->request = $request ?: Request::capture();
        $this->filesystem = $filesystem ?: new Filesystem();

        $config['chunks'] = empty($config['chunks']) === false ? $config['chunks'] : sys_get_temp_dir().'/chunks';
        $config['storage'] = empty($config['storage']) === false ? $config['storage'] : 'storage/temp';
        $config['domain'] = empty($config['domain']) === false ? $config['domain'] : $this->request->root();
        $config['path'] = empty($config['path']) === false ? $config['path'] : 'storage/temp';

        foreach (['chunks', 'storage', 'domain', 'path'] as $key) {
            $config[$key] = rtrim($config[$key], '/').'/';
        }
        $this->config = $config;
    }

    /**
     * chunksPath.
     *
     * @return string
     */
    public function chunksPath()
    {
        return $this->config['chunks'];
    }

    /**
     * storagePath.
     *
     * @return string
     */
    public function storagePath()
    {
        return $this->config['storage'];
    }

    /**
     * domain.
     *
     * @return string
     */
    public function domain()
    {
        return $this->config['domain'];
    }

    /**
     * path.
     *
     * @return string
     */
    public function path()
    {
        return $this->config['path'];
    }

    /**
     * makeDirectory.
     *
     * @return $this
     */
    public function makeDirectory($path)
    {
        if ($this->filesystem->isDirectory($path) === false) {
            $this->filesystem->makeDirectory($path, 0777, true, true);
        }

        return $this;
    }

    /**
     * cleanDirectory.
     */
    public function cleanDirectory($path)
    {
        $time = time();
        $maxFileAge = 3600;
        $files = (array) $this->filesystem->files($path);
        foreach ($files as $file) {
            if ($this->filesystem->isFile($file) === true &&
                $this->filesystem->lastModified($file) < ($time - $maxFileAge)
            ) {
                $this->filesystem->delete($file);
            }
        }
    }

    /**
     * receiveChunkedFile.
     *
     * @param string $originalName
     * @param string|resource $input
     * @param int $start
     * @param bool $completed
     * @param array $options
     * @return \Symfony\Component\HttpFoundation\File\UploadedFile
     *
     * @throws \Recca0120\Upload\Exceptions\ChunkedResponseException
     */
    protected function receiveChunkedFile($originalName, $input, $start, $completed = false, $options = [])
    {
        $tmpfilename = $this->filesystem->tmpfilename(
            $originalName, $this->request->get('token')
        );
        $chunkFile = $this->chunksPath().$tmpfilename.static::TMPFILE_EXTENSION;
        $storageFile = $this->storagePath().$tmpfilename;
        $this->filesystem->appendStream($chunkFile, $input, $start);
        if ($completed === false) {
            throw new ChunkedResponseException(
                empty($options['headers']) === false ? $options['headers'] : []
            );
        }
        $this->filesystem->move($chunkFile, $storageFile);

        return $this->filesystem->createUploadedFile(
            $storageFile,
            $originalName,
            empty($options['mimeType']) === false ? $options['mimeType'] : $this->filesystem->mimeType($originalName),
            $this->filesystem->size($storageFile)
        );
    }

    /**
     * receive.
     *
     * @param string $inputName
     * @return \Symfony\Component\HttpFoundation\File\UploadedFile
     *
     * @throws \Recca0120\Upload\Exceptions\ChunkedResponseException
     */
    public function receive($inputName)
    {
        $chunksPath = $this->chunksPath();
        $storagePath = $this->storagePath();
        $uploadedFile = $this->makeDirectory($chunksPath)
            ->makeDirectory($storagePath)
            ->doReceive($inputName);
        $this->cleanDirectory($chunksPath);

        return $uploadedFile;
    }

    /**
     * doReceive.
     *
     * @param string $inputName
     * @return \Symfony\Component\HttpFoundation\File\UploadedFile
     *
     * @throws \Recca0120\Upload\Exceptions\ChunkedResponseException
     */
    abstract protected function doReceive($inputName);

    /**
     * deleteUploadedFile.
     *
     * @param \Symfony\Component\HttpFoundation\File\UploadedFile
     * @return $this
     */
    public function deleteUploadedFile(UploadedFile $uploadedFile)
    {
        $file = $uploadedFile->getPathname();
        if ($this->filesystem->isFile($file) === true) {
            $this->filesystem->delete($file);
        }

        return $this;
    }

    /**
     * completedResponse.
     *
     * @param \Illuminate\Http\JsonResponse $response
     * @return \Illuminate\Http\JsonResponse
     */
    public function completedResponse(JsonResponse $response)
    {
        return $response;
    }
}
