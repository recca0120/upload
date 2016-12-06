<?php

namespace Recca0120\Upload\Uploaders;

use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class Plupload extends Base
{
    /**
     * receive.
     *
     * @param string $name
     *
     * @throws ChunkedResponseException
     *
     * @return \Symfony\Component\HttpFoundation\File\UploadedFile
     */
    public function receive($name)
    {
        $uploadedFile = $this->request->file($name);
        $chunks = $this->request->get('chunks');

        if (empty($chunks) === true) {
            return $uploadedFile;
        }

        $chunk = $this->request->get('chunk');
        $start = $chunk * $this->request->header('content-length');

        $originalName = $this->request->get('name');
        $mimeType = $uploadedFile->getMimeType();

        $isCompleted = ($chunk >= $chunks - 1);
        $input = $uploadedFile->getPathname();

        return $this->receiveChunkedFile($originalName, $input, $start, $mimeType, $isCompleted);
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
