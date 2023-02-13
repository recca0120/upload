<?php

namespace Recca0120\Upload;

use Illuminate\Http\JsonResponse;

class FineUploader extends Api
{
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
        }

        return $completed === true
            ? $chunkFile->createUploadedFile($totalparts)
            : $chunkFile->throwException(['success' => true, 'uuid' => $uuid]);
    }

    public function completedResponse(JsonResponse $response): JsonResponse
    {
        $data = $response->getData();
        $data->success = true;
        $data->uuid = $this->request->get('qquuid');
        $response->setData($data);

        return $response;
    }
}
