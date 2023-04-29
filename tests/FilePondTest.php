<?php

namespace Recca0120\Upload\Tests;

use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Recca0120\Upload\Exceptions\ChunkedResponseException;
use Recca0120\Upload\Exceptions\ResourceOpenException;
use Recca0120\Upload\FilePond;
use ReflectionClass;
use ReflectionException;

class FilePondTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->api = new FilePond($this->config, $this->request, $this->files);
        $this->request->files->remove('foo');
    }

    public function testReceiveSingleFile(): void
    {
        $this->request->files->replace(['foo' => $this->uploadedFile]);

        $this->assertSame($this->uploadedFile, $this->api->receive('foo'));
    }

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
        $this->request->setMethod('patch');
        $this->request->replace(['patch' => $this->uuid]);
        $content = $this->uploadedFile->getContent();

        $size = $this->uploadedFile->getSize();
        $name = $this->uploadedFile->getClientOriginalName();

        $length = 10;
        $loop = 2;
        $offset = 0;
        for ($i = 0; $i < $loop; $i++) {
            $offset = $i * $length;
            $this->setProperty(substr($content, $offset, $length));
            $this->request->headers->replace([
                'Upload-Length' => $size,
                'Upload-Name' => $name,
                'Upload-Offset' => $offset,
                'Content-Length' => $length,
            ]);

            try {
                $this->api->receive('foo');
            } catch (ChunkedResponseException $e) {
            }
        }

        $offset *= $loop;
        $this->setProperty(substr($content, $offset));
        $this->request->headers->replace([
            'Upload-Length' => $size,
            'Upload-Name' => $name,
            'Upload-Offset' => $offset,
            'Content-Length' => $size - $offset,
        ]);

        $uploadedFile = $this->api->receive('foo');
        self::assertTrue($uploadedFile->isValid());
        self::assertEquals($size, $uploadedFile->getSize());
    }

    /**
     * @throws ReflectionException
     */
    private function setProperty($value): void
    {
        $reflectedClass = new ReflectionClass($this->request);
        $reflection = $reflectedClass->getProperty('content');
        $reflection->setAccessible(true);
        $reflection->setValue($this->request, $value);
    }
}
