<?php

namespace Recca0120\Upload;

use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Response;

abstract class Api
{
    /**
     * $request.
     *
     * @var \Illuminate\Http\Request
     */
    protected $request;

    /**
     * $name.
     *
     * @var string
     */
    protected $name;

    /**
     * __construct.
     *
     * @method __construct
     *
     * @param \Illuminate\Http\Request $request
     */
    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    /**
     * setName.
     *
     * @method setName
     *
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * getFile.
     *
     * @method getFile
     *
     * @return \Symfony\Component\HttpFoundation\File\UploadedFile
     */
    public function getFile()
    {
        return $this->request->file($this->name);
    }

    /**
     * getPartialName.
     *
     * @method getPartialName
     *
     * @return string
     */
    public function getPartialName($chunkPath)
    {
        $originalName = $this->getOriginalName();
        $extension = $this->getExtension($originalName);
        $token = $this->request->get('token');

        return $chunkPath.md5($originalName.$token).$extension.'.part';
    }

    /**
     * getExtension.
     *
     * @method getExtension
     *
     * @param string $name
     *
     * @return string
     */
    public function getExtension($name)
    {
        $extension = null;
        if (($pos = strrpos($name, '.')) !== -1) {
            $extension = '.'.substr($name, $pos + 1);
        }

        return $extension;
    }

    /**
     * chunkedResponse.
     *
     * @method chunkedResponse
     *
     * @param \Symfony\Component\HttpFoundation\Response $response
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function chunkedResponse(Response $response)
    {
        return $response;
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

    /**
     * Returns the maximum size of an uploaded file as configured in php.ini.
     *
     * @return int The maximum size of an uploaded file in bytes
     */
    public function getMaxFilesize()
    {
        return UploadedFile::getMaxFilesize();
    }

    /**
     * getOriginalName.
     *
     * @method getOriginalName
     *
     * @return string
     */
    abstract public function getOriginalName();

    /**
     * getStartOffset.
     *
     * @method getStartOffset
     *
     * @return int
     */
    abstract public function getStartOffset();

    /**
     * hasChunks.
     *
     * @method hasChunks
     *
     * @return bool
     */
    abstract public function hasChunks();

    /**
     * isCompleted.
     *
     * @method isCompleted
     *
     * @return bool
     */
    abstract public function isCompleted();

    /**
     * getMimeType.
     *
     * @method getMimeType
     *
     * @return string
     */
    abstract public function getMimeType();

    /**
     * getResourceName.
     *
     * @method getResourceName
     *
     * @return string
     */
    abstract public function getResourceName();
}
