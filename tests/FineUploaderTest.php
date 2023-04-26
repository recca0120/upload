<?php

namespace Recca0120\Upload\Tests;

use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Http\JsonResponse;
use Recca0120\Upload\Exceptions\ChunkedResponseException;
use Recca0120\Upload\Exceptions\ResourceOpenException;
use Recca0120\Upload\FineUploader;

class FineUploaderTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->api = new FineUploader($this->config, $this->request, $this->files);
    }

    public function testReceiveSingleFile(): void
    {
        $this->assertSame($this->uploadedFile, $this->api->receive('foo'));

        $response = $this->api->completedResponse(new JsonResponse());

        self::assertEquals('{"success":true,"uuid":null}', $response->getContent());
    }

    /**
     * @throws FileNotFoundException
     * @throws ResourceOpenException
     */
    public function testReceiveChunkedFile(): void
    {
        $this->request->files->remove('foo');

        $tmpFile = $this->files->tmpfilename($this->uploadedFile->getClientOriginalName(), $this->uuid);
        for ($i = 0; $i < 3; $i++) {
            file_put_contents($this->root->url().'/chunks/'.$tmpFile.'.part.'.$i, '');
        }
        file_put_contents($this->root->url().'/chunks/'.$tmpFile.'.part.'.$i, $this->uploadedFile->getContent());

        $this->request->replace([
            'qqpartindex' => 3,
            'qqtotalparts' => 4,
            'qquuid' => $this->uuid,
            'qqfilename' => $this->uploadedFile->getClientOriginalName(),
        ]);

        self::assertTrue($this->api->receive('foo')->isValid());

        $response = $this->api->completedResponse(new JsonResponse());

        self::assertEquals('{"success":true,"uuid":"'.$this->uuid.'"}', $response->getContent());
    }

    /**
     * @throws FileNotFoundException
     * @throws ResourceOpenException
     */
    public function testReceiveChunkedFileWithParts(): void
    {
        $this->expectException(ChunkedResponseException::class);
        $this->expectExceptionMessage('{"success":true,"uuid":"'.$this->uuid.'"}');

        $this->request->replace([
            'qqpartindex' => 3,
            'qqtotalparts' => 4,
            'qquuid' => $this->uuid,
            'qqfilename' => $this->uploadedFile->getClientOriginalName(),
        ]);

        $this->api->receive('foo');
    }
}
