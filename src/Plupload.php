<?php

namespace Recca0120\Upload;

use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Http\JsonResponse;
use Recca0120\Upload\Exceptions\ChunkedResponseException;
use Recca0120\Upload\Exceptions\ResourceOpenException;

class Plupload extends Api
{
    /**
     * @throws FileNotFoundException
     * @throws ResourceOpenException
     */
    public function receive(string $name)
    {
        $uploadedFile = $this->request->file($name);
        $chunks = $this->request->get('chunks');
        if (empty($chunks) === true) {
            return $uploadedFile;
        }

        $chunk = $this->request->get('chunk');
        $originalName = $this->request->get('name');
        $start = $chunk * $this->request->header('content-length');
        $uuid = $this->request->get('token');
        $completed = $chunk >= $chunks - 1;

        $chunkFile = $this->createChunkFile($originalName, $uuid);
        $chunkFile->appendStream($uploadedFile->getPathname(), $start);

        if ($completed !== true) {
            throw new ChunkedResponseException('', []);
        }

        return $chunkFile->createUploadedFile();
    }

    public function completedResponse(JsonResponse $response): JsonResponse
    {
        $data = $response->getData();
        $response->setData(['jsonrpc' => '2.0', 'result' => $data]);

        return $response;
    }
}
