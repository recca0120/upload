<?php

namespace Recca0120\Upload;

use Illuminate\Http\JsonResponse;

class FineUploader extends Api
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
        $file = $this->request->file($name);
        if ($this->request->has('qqtotalparts') === false) {
            return $file;
        }

        $completed = is_null($file) === true;
        $originalName = $this->request->get('qqfilename');
        $qqtotalparts = (int) $this->request->get('qqtotalparts', 1);
        $qqpartindex = (int) $this->request->get('qqpartindex');
        $uuid = $this->request->get('qquuid');

        $this->chunkFile
            ->setToken($uuid)
            ->setChunkPath($this->chunkPath())
            ->setStoragePath($this->storagePath())
            ->setName($originalName);

        if ($completed === false) {
            $this->chunkFile->appendStream($file->getRealPath(), 0, $qqpartindex);
        }

        return $completed === true
            ? $this->chunkFile->createUploadedFile($qqtotalparts)
            : $this->chunkFile->throwException([
                'success' => true,
                'uuid' => $uuid,
            ]);
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
        $data->success = true;
        $data->uuid = $this->request->get('qquuid');
        $response->setData($data);

        return $response;
    }
}
