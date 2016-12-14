<?php

namespace Recca0120\Upload;

use Closure;
use Illuminate\Support\Arr;
use Illuminate\Http\JsonResponse;
use Recca0120\Upload\Apis\FileAPI;
use Recca0120\Upload\Apis\Plupload;
use Recca0120\Upload\Contracts\Api;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Recca0120\Upload\Exceptions\ChunkedResponseException;

class Receiver
{
    /**
     * $api.
     *
     * @var \Recca0120\Upload\Contracts\Api
     */
    protected $api;

    /**
     * $root.
     *
     * @var string
     */
    public $root = null;

    /**
     * $path.
     *
     * @var string
     */
    public $path = null;

    /**
     * $url.
     *
     * @var string
     */
    public $url = null;

    /**
     * __construct.
     *
     * @param \Recca0120\Upload\Contracts\Api  $api
     */
    public function __construct(Api $api)
    {
        $config = $api->getConfig();
        $this->setRoot(Arr::get($config, 'root', sys_get_temp_dir()));
        $this->setPath(Arr::get($config, 'path', '/storage/'));
        $this->setUrl(Arr::get($config, 'url'));
        $this->api = $api;
    }

    /**
     * getRoot.
     *
     * @return string
     */
    public function getRoot()
    {
        return rtrim($this->root, '/').'/';
    }

    /**
     * setRoot.
     *
     * @param string $root
     */
    public function setRoot($root)
    {
        $this->root = $root;

        return $this;
    }

    /**
     * setPath.
     *
     * @param string $path
     */
    public function setPath($path)
    {
        $this->path = $path;

        return $this;
    }

    /**
     * setUrl.
     *
     * @param string $url
     */
    public function setUrl($url)
    {
        $this->url = $url;

        return $this;
    }

    /**
     * receive.
     *
     * @param  string $name
     * @param  Closure $closure
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function receive($inputName = 'file', Closure $callback = null, $path = null)
    {
        $callback = is_null($callback) === true ? $this->callback() : $callback;
        $path = trim(is_null($path) === true ? $this->path : $path, '/');
        $root = $this->getRoot();

        try {
            $uploadedFile = $this->api
                ->makeDirectory($root.$path)
                ->receive($inputName);

            $response = $callback($uploadedFile, $path, $root, $this->url, $this->api);

            return $this->api
                ->deleteUploadedFile($uploadedFile)
                ->completedResponse($response);
        } catch (ChunkedResponseException $e) {
            return $e->getResponse();
        }
    }

    /**
     * callback.
     *
     * @return \Closure
     */
    protected function callback()
    {
        return function (UploadedFile $uploadedFile, $path, $root, $url, $api) {
            $storagePath = $root.$path;

            $clientOriginalName = $uploadedFile->getClientOriginalName();
            $clientOriginalExtension = strtolower($uploadedFile->getClientOriginalExtension());
            $basename = pathinfo($uploadedFile->getBasename(), PATHINFO_FILENAME);
            $filename = $basename.'.'.$clientOriginalExtension;
            $tempname = '/'.$path.$filename;
            $mimeType = $uploadedFile->getMimeType();
            $size = $uploadedFile->getSize();

            $uploadedFile->move($storagePath);

            $response = [
                'name' => $clientOriginalName,
                'tmp_name' => $tempname,
                'type' => $mimeType,
                'size' => $size,
            ];

            if (is_null($url) === false) {
                $response['url'] = rtrim($url, '/').'/'.$path.'/'.$filename;
            }

            return new JsonResponse($response);
        };
    }

    /**
     * factory.
     *
     * @param  array $config
     * @param  string $class
     *
     * @return \Recca0120\Upload\Contracts\Api
     */
    public static function factory($config = [], $class = FileAPI::class)
    {
        $map = [
            'fileapi' => FileAPI::class,
            'plupload' => Plupload::class,
        ];

        $class = isset($map[strtolower($class)]) === true ? $map[$class] : $class;

        return new static(new $class($config));
    }
}
