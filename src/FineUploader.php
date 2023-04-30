<?php

namespace Recca0120\Upload;

use Illuminate\Http\JsonResponse;
use Recca0120\Upload\Exceptions\ChunkedResponseException;

class FineUploader extends Api
{
    public function completedResponse(JsonResponse $response): JsonResponse
    {
        $data = $response->getData();
        $data->success = true;
        $data->uuid = $this->request->get('qquuid');

        return $response->setData($data);
    }

    protected function isChunked(string $name): bool
    {
        return $this->request->has('qqtotalparts');
    }

    protected function isCompleted(string $name): bool
    {
        return ! $this->request->hasFile($name);
    }

    protected function receiveChunked(string $name)
    {
        $uploadedFile = $this->request->file($name);
        $uuid = $this->request->get('qquuid');

        $chunkFile = $this->createChunkFile($this->request->get('qqfilename'), $uuid);

        if (! $this->isCompleted($name)) {
            $chunkFile->appendFile($uploadedFile->getRealPath(), (int) $this->request->get('qqpartindex'));

            throw new ChunkedResponseException(['success' => true, 'uuid' => $uuid]);
        }

        return $chunkFile->createUploadedFile((int) $this->request->get('qqtotalparts', 1));
    }
}
