<?php

namespace Recca0120\Upload\Tests;

use Mockery as m;
use Recca0120\Upload\Receiver;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Recca0120\Upload\Exceptions\ChunkedResponseException;

class ReceiverTest extends TestCase
{
    protected function tearDown()
    {
        m::close();
    }

    public function testReceive()
    {
        $api = m::mock('Recca0120\Upload\Contracts\Api');
        $api->shouldReceive('getConfig')->once()->andReturn([
            'root' => $root = 'foo/',
            'path' => $path = 'foo/',
            'url' => $url = 'foo',
        ]);
        $receiver = new Receiver($api);
        $inputName = 'foo';
        $api->shouldReceive('makeDirectory')->once()->with($root.$path)->andReturnSelf();
        $api->shouldReceive('receive')->once()->with($inputName)->andReturn(
            $uploadedFile = m::mock('Symfony\Component\HttpFoundation\File\UploadedFile')
        );
        $uploadedFile->shouldReceive('getClientOriginalName')->once()->andReturn(
            $clientOriginalName = 'foo.PHP'
        );
        $uploadedFile->shouldReceive('getClientOriginalExtension')->once()->andReturn(
            $clientOriginalExtension = 'PHP'
        );
        $uploadedFile->shouldReceive('getBasename')->once()->andReturn(
            $basename = 'foo'
        );
        $uploadedFile->shouldReceive('getMimeType')->once()->andReturn(
            $mimeType = 'foo'
        );
        $uploadedFile->shouldReceive('getSize')->once()->andReturn(
            $size = 1000
        );
        $uploadedFile->shouldReceive('move')->once()->with($root.$path);
        $api->shouldReceive('deleteUploadedFile')->once()->with($uploadedFile)->andReturnSelf();
        $api->shouldReceive('completedResponse')->once()->with(m::type('Illuminate\Http\JsonResponse'))->andReturnUsing(function ($response) {
            return $response;
        });
        $response = $receiver->receive($inputName);
        $this->assertSame([
            'name' => $clientOriginalName,
            'tmp_name' => $path.$basename.'.'.strtolower($clientOriginalExtension),
            'type' => $mimeType,
            'size' => $size,
            'url' => $path.$basename.'.'.strtolower($clientOriginalExtension),
        ], (array) $response->getData());
    }

    public function testReceiveCustomCallback()
    {
        $api = m::mock('Recca0120\Upload\Contracts\Api');
        $api->shouldReceive('getConfig')->once()->andReturn([
            'root' => $root = 'foo/',
            'path' => $path = 'foo/',
            'url' => $url = 'foo',
        ]);
        $receiver = new Receiver($api);
        $inputName = 'foo';
        $api->shouldReceive('makeDirectory')->once()->with($root.$path)->andReturnSelf();
        $api->shouldReceive('receive')->once()->with($inputName)->andReturn(
            $uploadedFile = m::mock('Symfony\Component\HttpFoundation\File\UploadedFile')
        );
        $response = m::mock('Illuminate\Http\JsonResponse');
        $api->shouldReceive('deleteUploadedFile')->once()->with($uploadedFile)->andReturnSelf();
        $api->shouldReceive('completedResponse')->once()->with($response)->andReturn($response);

        $this->assertSame(
            $response,
            $receiver->receive($inputName, function (UploadedFile $uploadedFile, $path, $root, $url, $api) use ($response) {
                return $response;
            })
        );
    }

    public function testReceiveAndThroChunkedResponseException()
    {
        $api = m::mock('Recca0120\Upload\Contracts\Api');
        $api->shouldReceive('getConfig')->once()->andReturn([
            'root' => $root = 'foo/',
            'path' => $path = 'foo/',
            'url' => $url = 'foo',
        ]);
        $receiver = new Receiver($api);
        $inputName = 'foo';
        $api->shouldReceive('makeDirectory')->once()->with($root.$path)->andReturnSelf();
        $api->shouldReceive('receive')->once()->with($inputName)->andThrow(
            $chunkedResponseException = new ChunkedResponseException()
        );
        $this->assertInstanceOf(
            'Symfony\Component\HttpFoundation\Response',
            $receiver->receive($inputName, function (UploadedFile $uploadedFile, $path, $root, $url, $api) {
                return $response;
            })
        );
    }

    public function testFactory()
    {
        $this->assertAttributeInstanceOf(
            'Recca0120\Upload\Apis\FileAPI',
            'api',
            Receiver::factory([], 'Recca0120\Upload\Apis\FileAPI')
        );
        $this->assertAttributeInstanceOf(
            'Recca0120\Upload\Apis\FileAPI',
            'api',
            Receiver::factory([], 'FILEAPI')
        );
        $this->assertAttributeInstanceOf(
            'Recca0120\Upload\Apis\Plupload',
            'api',
            Receiver::factory([], 'Recca0120\Upload\Apis\Plupload')
        );
        $this->assertAttributeInstanceOf(
            'Recca0120\Upload\Apis\Plupload',
            'api',
            Receiver::factory([], 'PLUPLOAD')
        );
    }
}
