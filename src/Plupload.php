<?php

namespace Recca0120\Upload;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\UploadedFile;
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

    protected function receiveChunked(string $name): UploadedFile
    {
        $uploadedFile = $this->request->file($name);
        $originalName = $this->request->get('name');
        $originalName = empty($originalName) ? $uploadedFile->getClientOriginalName() : $originalName;
        $chunkFile = $this->createChunkFile($originalName, $this->request->get('token'));
        $chunkFile->appendFile($uploadedFile->getPathname(), $this->request->get('chunk'));

        if (! $this->isCompleted($name)) {
            throw new ChunkedResponseException(['jsonrpc' => '2.0', 'result' => false]);
        }

        return $chunkFile->createUploadedFile($this->request->get('chunks'));
    }
}
