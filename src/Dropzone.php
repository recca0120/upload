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
        $file = $this->request->file($name);
        if ($this->request->has('dztotalchunkcount') === false) {
            return $file;
        }

        $originalName = $file->getClientOriginalName();
        $totalparts = (int) $this->request->get('dztotalchunkcount', 1);
        $partindex = (int) $this->request->get('dzchunkindex');
        $uuid = $this->request->get('dzuuid');

        $chunkFile = $this->createChunkFile($originalName, $uuid);
        $chunkFile->appendFile($file->getRealPath(), $partindex);
        $completed = $totalparts - 1 === $partindex;

        if ($completed !== true) {
            throw new ChunkedResponseException(['success' => true, 'uuid' => $uuid], []);
        }

        return $chunkFile->createUploadedFile($totalparts);
    }

    public function completedResponse(JsonResponse $response): JsonResponse
    {
        $data = $response->getData();
        $data->success = true;
        $data->uuid = $this->request->get('dzuuid');
        $response->setData($data);

        return $response;
    }
}
