<?php

namespace Recca0120\Upload\Apis;

use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class FileAPI extends Api
{
    /**
     * $hasChunks.
     *
     * @var bool
     */
    public $hasChunks = false;

    /**
     * $start.
     *
     * @var int
     */
    public $start;

    /**
     * $end.
     *
     * @var int
     */
    public $end;

    /**
     * $total.
     *
     * @var int
     */
    public $total;

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
        $this->parseContentRange();
    }

    /**
     * parseContentRange.
     *
     * @method parseContentRange
     */
    protected function parseContentRange()
    {
        $contentRange = $this->request->header('content-range');
        if (empty($contentRange)) {
            return;
        }
        list($start, $end, $total) = sscanf($contentRange, 'bytes %d-%d/%d');
        $this->start = (int) $start;
        $this->end = (int) $end;
        $this->total = (int) $total;
        $this->hasChunks = true;
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
        $name = $this->request->get('name');
        if (is_null($name) === false) {
            return $name;
        }

        list($name) = sscanf($this->request->header('content-disposition'), 'attachment; filename=%s');

        return $name;
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
        return $this->hasChunks;
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
        return $this->start;
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
        return $this->end >= $this->total - 1;
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
        $response->headers->set('X-Last-Known-Byte', $this->end);

        return $response;
    }
}
