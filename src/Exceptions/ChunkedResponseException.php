<?php

namespace Recca0120\Upload\Exceptions;

use RuntimeException;
use Symfony\Component\HttpFoundation\Response;

class ChunkedResponseException extends RuntimeException
{
    /**
     * $message.
     *
     * @var string
     */
    protected $message;

    /**
     * $headers.
     *
     * @var array
     */
    protected $headers;

    /**
     * __construct.
     *
     * @param mixed $message
     * @param array $headers
     * @param int $code
     */
    public function __construct($message = '', $headers = [], $code = Response::HTTP_CREATED)
    {
        parent::__construct(
            is_string($message) === true ? $message : json_encode($message), $code
        );

        $this->headers = $headers;
    }

    /**
     * getResponse.
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function getResponse()
    {
        return new Response($this->getMessage(), $this->getCode(), $this->headers);
    }
}
