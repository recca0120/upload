<?php

namespace Recca0120\Upload;

use Symfony\Component\HttpFoundation\Response;

class Plupload extends Api
{
    /**
     * getOriginalName.
     *
     * @method getOriginalName
     *
     * @return string
     */
    public function getOriginalName()
    {
        return $this->request->get('name');
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
        return is_null($this->request->get('chunks')) === false;
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
        $chunk = (int) $this->request->get('chunk', 1);
        $chunks = (int) $this->request->get('chunks', 1);
        $length = (int) $this->request->header('content-length');

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
        $chunk = (int) $this->request->get('chunk', 1);
        $chunks = (int) $this->request->get('chunks', 1);

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
     * replaceResponse.
     *
     * @method replaceResponse
     *
     * @param  \Symfony\Component\HttpFoundation\Response $response
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function replaceResponse(Response $response)
    {
        $data = $response->getData();
        $response->setData([
            'jsonrpc' => '2.0',
            'result'  => $data,
        ]);

        return $response;
    }
}
