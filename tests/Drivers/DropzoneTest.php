<?php

namespace Recca0120\Upload\Tests\Drivers;

use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Http\JsonResponse;
use Recca0120\Upload\Drivers\Dropzone;
use Recca0120\Upload\Exceptions\ChunkedResponseException;
use Recca0120\Upload\Exceptions\ResourceOpenException;
use Recca0120\Upload\Tests\TestCase;
use ReflectionException;

class DropzoneTest extends TestCase
{
    /**
     * @var Dropzone
     */
    private $api;

    protected function setUp(): void
    {
        parent::setUp();

        $this->api = new Dropzone($this->config, $this->request, $this->files);
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
        $this->chunkUpload(3, function ($offset, $chunkSize, $index, $totalCount) use ($size) {
            $this->request->replace([
                'dzuuid' => $this->uuid,
                'dzchunkindex' => $index,
                'dztotalfilesize' => $size,
                'dzchunksize' => $chunkSize,
                'dztotalchunkcount' => $totalCount,
                'dzchunkbyteoffset' => $offset,
            ]);
            try {
                $uploadedFile = $this->api->receive('foo');
                self::assertEquals($size, $uploadedFile->getSize());
            } catch (ChunkedResponseException $e) {
                self::assertStringMatchesFormat(
                    '{"success":true,"uuid":"'.$this->uuid.'"}',
                    $e->getMessage()
                );
            }
        });
    }

    public function testResponse(): void
    {
        $response = $this->api->completedResponse(new JsonResponse());

        self::assertEquals('{"success":true,"uuid":null}', $response->getContent());
    }
}
