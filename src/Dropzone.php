<?php

namespace Recca0120\Upload;

use Illuminate\Http\JsonResponse;

class Dropzone extends FineUploader
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
        if ($this->request->has('dztotalchunkcount') === false) {
            return $file;
        }

        $originalName = $file->getClientOriginalName();
        $totalparts = (int) $this->request->get('dztotalchunkcount', 1);
        $partindex = (int) $this->request->get('dzchunkindex');
        $uuid = $this->request->get('dzuuid');

        $this->chunkFile
            ->setToken($uuid)
            ->setChunkPath($this->chunkPath())
            ->setStoragePath($this->storagePath())
            ->setName($originalName)
            ->appendFile($file->getRealPath(), $partindex);

        $completed = $totalparts - 1 === $partindex;

        return $completed === true
            ? $this->chunkFile->createUploadedFile($totalparts)
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
        $data->uuid = $this->request->get('dzuuid');
        $response->setData($data);

        return $response;
    }
}
