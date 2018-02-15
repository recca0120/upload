<?php

namespace Recca0120\Upload;

use Illuminate\Http\JsonResponse;

class Plupload extends Api
{
    /**
     * receive.
     *
     * @param string $name
     * @return \Symfony\Component\HttpFoundation\File\UploadedFile
     *
     * @throws \Recca0120\Upload\Exceptions\ChunkedResponseException
     */
    public function receive($name)
    {
        $uploadedFile = $this->request->file($name);
        $chunks = $this->request->get('chunks');
        if (empty($chunks) === true) {
            return $uploadedFile;
        }
        $chunk = $this->request->get('chunk');

        $originalName = $this->request->get('name');
        $start = $chunk * $this->request->header('content-length');
        $uuid = $this->request->get('token');
        $completed = $chunk >= $chunks - 1;

        $chunkFile = $this->createChunkFile($originalName, $uuid);
        $chunkFile->appendStream($uploadedFile->getPathname(), $start);

        return $completed === true
            ? $chunkFile->createUploadedFile()
            : $chunkFile->throwException();
    }

    /**
     * completedResponse.
     *
     * @param \Illuminate\Http\JsonResponse $response
     * @return \Illuminate\Http\JsonResponse
     */
    public function completedResponse(JsonResponse $response)
    {
        $data = $response->getData();
        $response->setData([
            'jsonrpc' => '2.0',
            'result' => $data,
        ]);

        return $response;
    }
}
