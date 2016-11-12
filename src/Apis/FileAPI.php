<?php

namespace Recca0120\Upload\Apis;

use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class FileAPI extends Api
{
    /**
     * boot.
     *
     * @method boot
     */
    protected function boot()
    {
        $start = 0;
        $end = 0;
        $total = 0;
        $hasChunks = false;

        $contentRange = $this->request->header('content-range');
        if (empty($contentRange) === false) {
            list($start, $end, $total) = sscanf($contentRange, 'bytes %d-%d/%d');
            $hasChunks = true;
        }

        $originalName = $this->request->get('name');
        if (is_null($originalName) === true) {
            list($originalName) = sscanf($this->request->header('content-disposition'), 'attachment; filename=%s');
        }

        $this->attributes = [
            'originalName' => $originalName,
            'start' => (int) $start,
            'end' => (int) $end,
            'total' => (int) $total,
            'hasChunks' => $hasChunks,
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
     * getStartOffset.
     *
     * @method getStartOffset
     *
     * @return int
     */
    public function getStartOffset()
    {
        return $this->attributes['start'];
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
        return $this->attributes['end'] >= $this->attributes['total'] - 1;
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
        return $this->request->header('content-type');
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
        return 'php://input';
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
        $response->headers->set('X-Last-Known-Byte', $this->attributes['end']);

        return $response;
    }
}
