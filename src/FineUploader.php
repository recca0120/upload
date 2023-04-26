<?php

namespace Recca0120\Upload;

use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Http\JsonResponse;
use Recca0120\Upload\Exceptions\ChunkedResponseException;
use Recca0120\Upload\Exceptions\ResourceOpenException;

class FineUploader extends Api
{
    /**
     * @throws FileNotFoundException
     * @throws ResourceOpenException
     */
    public function receive(string $name)
    {
        $file = $this->request->file($name);
        if ($this->request->has('qqtotalparts') === false) {
            return $file;
        }

        $completed = is_null($file) === true;
        $originalName = $this->request->get('qqfilename');
        $totalparts = (int) $this->request->get('qqtotalparts', 1);
        $partindex = (int) $this->request->get('qqpartindex');
        $uuid = $this->request->get('qquuid');

        $chunkFile = $this->createChunkFile($originalName, $uuid);

        if ($completed === false) {
            $chunkFile->appendFile($file->getRealPath(), $partindex);
            throw new ChunkedResponseException(['success' => true, 'uuid' => $uuid], []);
        }

        return $chunkFile->createUploadedFile($totalparts);
    }

    public function completedResponse(JsonResponse $response): JsonResponse
    {
        $data = $response->getData();
        $data->success = true;
        $data->uuid = $this->request->get('qquuid');

        return $response->setData($data);
    }
}
