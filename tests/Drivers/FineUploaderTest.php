<?php

namespace Recca0120\Upload\Tests\Drivers;

use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Http\JsonResponse;
use Recca0120\Upload\Drivers\FineUploader;
use Recca0120\Upload\Exceptions\ChunkedResponseException;
use Recca0120\Upload\Exceptions\ResourceOpenException;
use Recca0120\Upload\Tests\TestCase;
use ReflectionException;

class FineUploaderTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->api = new FineUploader($this->config, $this->request, $this->files);
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
     * @throws FileNotFoundException
     * @throws ResourceOpenException
     * @throws ReflectionException
     */
    public function testReceiveChunkedFile(): void
    {
        $size = $this->uploadedFile->getSize();
        $this->chunkUpload(4, function ($offset, $chunkSize, $index, $totalCount) use ($size) {
            $this->request->replace([
                'qqpartindex' => $index,
                'qqpartbyteoffset' => $offset,
                'qqchunksize' => $chunkSize,
                'qqtotalparts' => $totalCount,
                'qqtotalfilesize' => $size,
                'qqfilename' => $this->uploadedFile->getClientOriginalName(),
                'qquuid' => $this->uuid,
            ]);

            try {
                $this->api->receive('foo');
            } catch (ChunkedResponseException $e) {
                self::assertStringMatchesFormat(
                    '{"success":true,"uuid":"'.$this->uuid.'"}',
                    $e->getMessage()
                );
            }
        });

        $this->request->files->remove('foo');
        $this->request->replace([
            'qqtotalfilesize' => $size,
            'qqfilename' => $this->uploadedFile->getClientOriginalName(),
            'qquuid' => $this->uuid,
        ]);

        $uploadedFile = $this->api->receive('foo');
        self::assertEquals($size, $uploadedFile->getSize());
    }

    public function testResponse(): void
    {
        $response = $this->api->completedResponse(new JsonResponse());

        self::assertEquals('{"success":true,"uuid":null}', $response->getContent());
    }
}
