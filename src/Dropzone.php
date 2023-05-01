<?php

namespace Recca0120\Upload;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\UploadedFile;
use Recca0120\Upload\Exceptions\ChunkedResponseException;

class Dropzone extends FineUploader
{
    public function completedResponse(JsonResponse $response): JsonResponse
    {
        $data = $response->getData();
        $data->success = true;
        $data->uuid = $this->request->get('dzuuid');

        return $response->setData($data);
    }

    protected function isChunked(string $name): bool
    {
        return $this->request->has('dztotalchunkcount');
    }

    protected function isCompleted(string $name): bool
    {
        $totalChunkCount = (int) $this->request->get('dztotalchunkcount', 1);
        $chunkIndex = (int) $this->request->get('dzchunkindex');

        return $totalChunkCount - 1 === $chunkIndex;
    }

    protected function receiveChunked(string $name): UploadedFile
    {
        $uploadedFile = $this->request->file($name);
        $originalName = $uploadedFile->getClientOriginalName();
        $totalparts = (int) $this->request->get('dztotalchunkcount', 1);
        $uuid = $this->request->get('dzuuid');

        $chunkFile = $this->createChunkFile($originalName, $uuid);
        $chunkFile->appendFile(
            $uploadedFile->getRealPath(),
            (int) $this->request->get('dzchunkindex')
        );

        if (! $this->isCompleted($name)) {
            throw new ChunkedResponseException(['success' => true, 'uuid' => $uuid]);
        }

        return $chunkFile->createUploadedFile($totalparts);
    }
}
