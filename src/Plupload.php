<?php

namespace Recca0120\Upload;

use Illuminate\Http\JsonResponse;
use Recca0120\Upload\Exceptions\ChunkedResponseException;

class Plupload extends Api
{
    public function completedResponse(JsonResponse $response): JsonResponse
    {
        return $response->setData(['jsonrpc' => '2.0', 'result' => $response->getData()]);
    }

    protected function isChunked(string $name): bool
    {
        return ! empty($this->request->get('chunks'));
    }

    protected function isCompleted(string $name): bool
    {
        $chunks = $this->request->get('chunks');
        $chunk = $this->request->get('chunk');

        return $chunk >= $chunks - 1;
    }

    protected function receiveChunked(string $name)
    {
        $uploadedFile = $this->request->file($name);
        $chunk = $this->request->get('chunk');
        $originalName = $this->request->get('name');
        $originalName = empty($originalName) ? $uploadedFile->getClientOriginalName() : $originalName;
        $start = $chunk * $this->request->header('content-length');
        $uuid = $this->request->get('token');

        $chunkFile = $this->createChunkFile($originalName, $uuid);
        $chunkFile->appendStream($uploadedFile->getPathname(), $start);

        if (! $this->isCompleted($name)) {
            throw new ChunkedResponseException(['jsonrpc' => '2.0', 'result' => false]);
        }

        return $chunkFile->createUploadedFile();
    }
}
