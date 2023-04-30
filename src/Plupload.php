<?php

namespace Recca0120\Upload;

use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Http\JsonResponse;
use Recca0120\Upload\Exceptions\ChunkedResponseException;
use Recca0120\Upload\Exceptions\ResourceOpenException;

class Plupload extends Api
{
    /**
     * @throws FileNotFoundException
     * @throws ResourceOpenException
     */
    public function receive(string $name)
    {
        if ($this->isChunked($name)) {
            return $this->request->file($name);
        }

        $uploadedFile = $this->request->file($name);
        $chunks = $this->request->get('chunks');
        $chunk = $this->request->get('chunk');
        $originalName = $this->request->get('name');
        $originalName = empty($originalName) ? $uploadedFile->getClientOriginalName() : $originalName;
        $start = $chunk * $this->request->header('content-length');
        $uuid = $this->request->get('token');
        $completed = $chunk >= $chunks - 1;

        $chunkFile = $this->createChunkFile($originalName, $uuid);
        $chunkFile->appendStream($uploadedFile->getPathname(), $start);

        if ($completed !== true) {
            throw new ChunkedResponseException(['jsonrpc' => '2.0', 'result' => false]);
        }

        return $chunkFile->createUploadedFile();
    }

    public function completedResponse(JsonResponse $response): JsonResponse
    {
        return $response->setData(['jsonrpc' => '2.0', 'result' => $response->getData()]);
    }

    protected function isChunked(string $name): bool
    {
        return empty($this->request->get('chunks'));
    }
}
