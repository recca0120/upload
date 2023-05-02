<?php

namespace Recca0120\Upload\Tests;

use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Http\JsonResponse;
use Recca0120\Upload\Exceptions\ChunkedResponseException;
use Recca0120\Upload\Exceptions\ResourceOpenException;
use Recca0120\Upload\FileAPI;
use ReflectionException;

class FileAPITest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->api = new FileAPI($this->config, $this->request, $this->files);
    }

    /**
     * @throws FileNotFoundException
     * @throws ResourceOpenException
     */
    public function testReceiveSingleFile(): void
    {
        $this->assertTrue($this->api->receive('foo')->isFile());
    }

    /**
     * @throws ReflectionException
     * @throws ResourceOpenException
     * @throws FileNotFoundException
     */
    public function testReceiveChunkedFile(): void
    {
        $size = $this->uploadedFile->getSize();
        $this->chunkUpload(4, function ($offset, $chunkSize) use ($size) {
            $start = $offset * $chunkSize;
            $end = $offset + $chunkSize - 1;
            $total = $size;

            $this->request->headers->replace([
                'content-disposition' => 'attachment; filename="'.$this->uploadedFile->getClientOriginalName().'"',
                'content-range' => "bytes {$start}-{$end}/${total}",
                'content-type' => $this->uploadedFile->getMimeType(),
                'content-length' => $chunkSize,
            ]);

            try {
                $uploadedFile = $this->api->receive('foo');
                self::assertEquals($size, $uploadedFile->getSize());
            } catch (ChunkedResponseException $e) {
                self::assertStringMatchesFormat(
                    '{"files":{"name":"test.png","size":%d,"type":"%s"}}',
                    $e->getMessage()
                );
            }
        });
    }

    /**
     * @throws FileNotFoundException
     * @throws ResourceOpenException
     */
    public function testReceiveChunkedFileWithoutContentRange(): void
    {
        $this->request->headers->replace([
            'content-disposition' => 'attachment; filename="'.$this->uploadedFile->getClientOriginalName().'"',
            'content-length' => $this->uploadedFile->getSize(),
            'content-type' => 'image/png',
        ]);

        self::assertTrue($this->api->receive('foo')->isValid());
    }

    public function testResponse(): void
    {
        $response = $this->api->completedResponse(new JsonResponse());

        self::assertEquals('{}', $response->getContent());
    }
}
