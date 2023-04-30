<?php

namespace Recca0120\Upload\Tests;

use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Recca0120\Upload\Exceptions\ChunkedResponseException;
use Recca0120\Upload\Exceptions\ResourceOpenException;
use Recca0120\Upload\FilePond;
use ReflectionException;

class FilePondTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->api = new FilePond($this->config, $this->request, $this->files);
        $this->request->files->remove('foo');
    }

    /**
     * @throws FileNotFoundException
     * @throws ResourceOpenException
     */
    public function testReceiveSingleFile(): void
    {
        $this->request->files->replace(['foo' => $this->uploadedFile]);

        $this->assertSame($this->uploadedFile, $this->api->receive('foo'));
    }

    /**
     * @throws FileNotFoundException
     * @throws ResourceOpenException
     */
    public function testReceiveChunkedFileAndThrowUniqIdChunkedResponseException(): void
    {
        $this->expectException(ChunkedResponseException::class);
        $this->expectExceptionMessageMatches('/\w+/');

        $this->request->replace(['file' => '{}']);
        $this->request->headers->replace(['Upload-Length' => $this->uploadedFile->getSize()]);
        $this->api->receive('foo');
    }

    /**
     * @throws ReflectionException
     * @throws ResourceOpenException
     * @throws FileNotFoundException
     */
    public function testReceiveChunkedFile(): void
    {
        $size = $this->uploadedFile->getSize();

        $this->request->replace(['file' => '{}']);
        $this->request->headers->replace(['Upload-Length' => $this->uploadedFile->getSize()]);
        $uuid = '';
        try {
            $this->api->receive('foo');
        } catch (ChunkedResponseException $e) {
            $uuid = $e->getMessage();
        }
        self::assertNotEmpty($uuid);

        $this->request->setMethod('patch');
        $this->request->replace(['patch' => $uuid]);

        $this->chunkUpload(3, function ($offset, $chunkSize) use ($size) {
            $this->request->headers->replace([
                'Upload-Length' => $size,
                'Upload-Name' => $this->uploadedFile->getClientOriginalName(),
                'Upload-Offset' => $offset,
                'Content-Length' => $chunkSize,
            ]);
            try {
                $uploadedFile = $this->api->receive('foo');
                self::assertEquals($size, $uploadedFile->getSize());
            } catch (ChunkedResponseException $e) {
                self::assertEquals(204, $e->getResponse()->getStatusCode());
            }
        });
    }
}
