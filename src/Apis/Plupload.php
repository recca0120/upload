<?php

namespace Recca0120\Upload\Apis;

use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class Plupload extends Api
{
    /**
     * boot.
     *
     * @method boot
     */
    protected function boot()
    {
        $chunks = $this->request->get('chunks');
        $this->attributes = [
            'originalName' => $this->request->get('name'),
            'hasChunks' => is_null($chunks) === false,
            'chunks' => is_null($chunks) === true ? 1 : (int) $chunks,
            'chunk' => (int) $this->request->get('chunk', 1),
            'content-length' => (int) $this->request->header('content-length'),
        ];
    }

    /**
     * getOriginalName.
     *
     * @method getOriginalName
     *
     * @return string
     */
    public function getOriginalName()
    {
        return $this->attributes['originalName'];
    }

    /**
     * hasChunks.
     *
     * @method hasChunks
     *
     * @return bool
     */
    public function hasChunks()
    {
        return $this->attributes['hasChunks'];
    }

    /**
     * getChunk.
     *
     * @method getChunk
     *
     * @return int
     */
    public function getChunk()
    {
        return $this->attributes['chunk'];
    }

    /**
     * getChunks.
     *
     * @method getChunks
     *
     * @return int
     */
    public function getChunks()
    {
        return $this->attributes['chunks'];
    }

    /**
     * getStartOffset.
     *
     * @method getStartOffset
     *
     * @return int
     */
    public function getStartOffset()
    {
        $chunk = $this->attributes['chunk'];
        $chunks = $this->attributes['chunks'];
        $length = $this->attributes['content-length'];

        return ($chunk >= $chunks - 1) ? null : $chunk * $length;
    }

    /**
     * isCompleted.
     *
     * @method isCompleted
     *
     * @return bool
     */
    public function isCompleted()
    {
        $chunk = $this->attributes['chunk'];
        $chunks = $this->attributes['chunks'];

        return $chunk >= $chunks - 1;
    }

    /**
     * getMimeType.
     *
     * @method getMimeType
     *
     * @return string
     */
    public function getMimeType()
    {
        return $this->getFile()->getMimeType();
    }

    /**
     * getResourceName.
     *
     * @method getResourceName
     *
     * @return string
     */
    public function getResourceName()
    {
        return $this->getFile()->getPathname();
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
        $data = $response->getData();
        $response->setData([
            'jsonrpc' => '2.0',
            'result' => $data,
        ]);

        return $response;
    }
}
