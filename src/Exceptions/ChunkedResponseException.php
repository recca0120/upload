<?php

namespace Recca0120\Upload\Exceptions;

use Exception;
use Symfony\Component\HttpFoundation\Response;

class ChunkedResponseException extends Exception
{
    /**
     * $headers.
     *
     * @var array
     */
    protected $headers;

    public function __construct($headers = [], $code = Response::HTTP_CREATED)
    {
        parent::__construct('', $code);
        $this->headers = $headers;
    }

    /**
     * getResponse.
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function getResponse()
    {
        return new Response(null, $this->getCode(), $this->headers);
    }
}
