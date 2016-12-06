<?php

namespace Recca0120\Upload\Uploader;

use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class Plupload extends Uploader
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
    public function get($name)
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
        $tmpfile = $this->receive($originalName, $input, $start, $isCompleted);
        $size = $this->filesystem->size($tmpfile);

        return $this->filesystem->createUploadedFile($tmpfile, $originalName, $mimeType, $size);
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
