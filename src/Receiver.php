<?php

namespace Recca0120\Upload;

use Closure;
use Illuminate\Support\Arr;
use Illuminate\Http\JsonResponse;
use Recca0120\Upload\Contracts\Uploader;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Recca0120\Upload\Exceptions\ChunkedResponseException;

class Receiver
{
    /**
     * $uploader.
     *
     * @var \Recca0120\Upload\Contracts\Uploader
     */
    protected $uploader;

    /**
     * $basePath.
     *
     * @var string
     */
    public $basePath = null;

    /**
     * $baseUrl.
     *
     * @var string
     */
    public $baseUrl = null;

    /**
     * __construct.
     *
     * @param \Recca0120\Upload\Contracts\Uploader  $uploader
     */
    public function __construct(Uploader $uploader)
    {
        $config = $uploader->getConfig();
        $this->setBasePath(Arr::get($config, 'base_path'));
        $this->setBaseUrl(Arr::get($config, 'base_url'));
        $this->uploader = $uploader;
    }

    /**
     * setBasePath.
     *
     * @param string $basePath
     */
    public function setBasePath($basePath)
    {
        $this->basePath = $basePath;

        return $this;
    }

    /**
     * getBasePath.
     *
     * @return string
     */
    public function getBasePath()
    {
        $basePath = is_null($this->basePath) === true ? sys_get_temp_dir() : $this->basePath;

        return rtrim($basePath, '/').'/';
    }

    /**
     * setBaseUrl.
     *
     * @param string $baseUrl
     */
    public function setBaseUrl($baseUrl)
    {
        $this->baseUrl = $baseUrl;

        return $this;
    }

    /**
     * receive.
     *
     * @param  string $name
     * @param  Closure $closure
     * @param  string $destinationPath
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function receive($inputName = 'file', Closure $callback = null, $destinationPath = 'storage/temp')
    {
        $callback = is_null($callback) === true ? $this->callback() : $callback;
        $absolutePath = $this->getBasePath().$destinationPath;

        try {
            $uploadedFile = $this->uploader
                ->makeDirectory($absolutePath)
                ->receive($inputName);

            $response = $callback($uploadedFile, $destinationPath, $absolutePath, $this->baseUrl);

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
     * @param  string $inputName
     * @param  string $destinationPath
     *
     * @return Closure
     */
    public function save($inputName, $destinationPath = 'storage/temp')
    {
        return $this->receive($inputName, null, $destinationPath);
    }

    /**
     * callback.
     *
     * @return \Closure
     */
    protected function callback()
    {
        return function (UploadedFile $uploadedFile, $destinationPath, $absolutePath, $baseUrl) {
            $clientOriginalName = $uploadedFile->getClientOriginalName();
            $clientOriginalExtension = strtolower($uploadedFile->getClientOriginalExtension());
            $basename = pathinfo($uploadedFile->getBasename(), PATHINFO_FILENAME);
            $filename = $basename.'.'.$clientOriginalExtension;
            $tempname = $destinationPath.'/'.$filename;
            $mimeType = $uploadedFile->getMimeType();
            $size = $uploadedFile->getSize();

            $uploadedFile->move($absolutePath, $filename);

            return $this->makeJsonResponse([
                'name' => $clientOriginalName,
                'tmp_name' => $tempname,
                'type' => $mimeType,
                'size' => $size,
            ], $baseUrl);
        };
    }

    /**
     * makeJsonResponse.
     *
     * @param array $data
     * @param string $baseUrl
     *
     * @return \Illuminate\Http\JsonResponse
     */
    protected function makeJsonResponse($data, $baseUrl = null)
    {
        if (is_null($baseUrl) === false) {
            $data['url'] = rtrim($baseUrl, '/').'/'.$data['tmp_name'];
        }

        return new JsonResponse($data);
    }
}
