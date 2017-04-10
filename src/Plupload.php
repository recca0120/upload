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
        $completed = $chunk >= $chunks - 1;

        $this->chunkFile
            ->setToken($this->request->get('token'))
            ->setChunkPath($this->chunkPath())
            ->setStoragePath($this->storagePath())
            ->setName($originalName)
            ->setMimeType($uploadedFile->getMimeType())
            ->appendStream($uploadedFile->getPathname(), $start);

        return $completed === true
            ? $this->chunkFile->createUploadedFile()
            : $this->chunkFile->throwException();
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
