<?php

namespace Recca0120\Upload;

use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Http\JsonResponse;
use Recca0120\Upload\Exceptions\ChunkedResponseException;
use Recca0120\Upload\Exceptions\ResourceOpenException;

class Dropzone extends FineUploader
{
    /**
     * @throws ResourceOpenException
     * @throws FileNotFoundException
     */
    public function receive(string $name)
    {
        if (! $this->isChunked($name)) {
            return $this->request->file($name);
        }

        $uploadedFile = $this->request->file($name);
        $originalName = $uploadedFile->getClientOriginalName();
        $totalparts = (int) $this->request->get('dztotalchunkcount', 1);
        $partindex = (int) $this->request->get('dzchunkindex');
        $uuid = $this->request->get('dzuuid');

        $chunkFile = $this->createChunkFile($originalName, $uuid);
        $chunkFile->appendFile($uploadedFile->getRealPath(), $partindex);

        if (! $this->isCompleted($name)) {
            throw new ChunkedResponseException(['success' => true, 'uuid' => $uuid], []);
        }

        return $chunkFile->createUploadedFile($totalparts);
    }

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
        $totalparts = (int) $this->request->get('dztotalchunkcount', 1);
        $partindex = (int) $this->request->get('dzchunkindex');

        return $totalparts - 1 === $partindex;
    }
}
