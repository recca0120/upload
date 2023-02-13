<?php

namespace Recca0120\Upload\Tests;

use Illuminate\Http\JsonResponse;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Mockery as m;
use PHPUnit\Framework\TestCase;
use Recca0120\Upload\Contracts\Api;
use Recca0120\Upload\Exceptions\ChunkedResponseException;
use Recca0120\Upload\FileAPI;
use Recca0120\Upload\Plupload;
use Recca0120\Upload\Receiver;
use ReflectionClass;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Response;

class ReceiverTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testReceive(): void
    {
        $receiver = new Receiver($api = m::mock(Api::class));
        $inputName = 'foo';
        $api->allows('receive')->once()->with($inputName)->andReturn($uploadedFile = m::mock(UploadedFile::class));
        $api->allows('domain')->once()->andReturn($domain = 'foo/');
        $api->allows('path')->once()->andReturn($path = 'foo/');
        $uploadedFile->allows('getClientOriginalName')->once()->andReturn($clientOriginalName = 'foo.PHP');
        $clientOriginalExtension = 'PHP';

        $uploadedFile->allows('getBasename')->once()->andReturn($basename = 'foo');
        $uploadedFile->allows('getMimeType')->once()->andReturn($mimeType = 'foo');
        $uploadedFile->allows('getSize')->once()->andReturn($size = 1000);
        $uploadedFile->allows('move')->once()->with($path, $filename = md5($basename).'.'.strtolower($clientOriginalExtension));

        $api->allows('deleteUploadedFile')->once()->with($uploadedFile)->andReturnSelf();
        $api->allows('completedResponse')->once()->with(m::type(JsonResponse::class))->andReturnUsing(function ($response) {
            return $response;
        });

        $response = $receiver->receive($inputName);
        $this->assertSame([
            'name' => pathinfo($clientOriginalName, PATHINFO_FILENAME).'.'.strtolower($clientOriginalExtension),
            'tmp_name' => $path.$filename,
            'type' => $mimeType,
            'size' => $size,
            'url' => $domain.$path.$filename,
        ], (array) $response->getData());
    }

    public function testReceiveCustomCallback(): void
    {
        $receiver = new Receiver($api = m::mock(Api::class));
        $inputName = 'foo';
        $api->allows('receive')->once()->with($inputName)->andReturn($uploadedFile = m::mock(UploadedFile::class));
        $api->allows('domain')->once()->andReturn($domain = 'foo/');
        $api->allows('path')->once()->andReturn($path = 'foo/');
        $response = m::mock(JsonResponse::class);
        $api->allows('deleteUploadedFile')->once()->with($uploadedFile)->andReturnSelf();
        $api->allows('completedResponse')->once()->with($response)->andReturn($response);

        $this->assertSame(
            $response,
            $receiver->receive($inputName, function () use ($response) {
                return $response;
            })
        );
    }

    public function testReceiveAndThrowChunkedResponseException(): void
    {
        $receiver = new Receiver($api = m::mock(Api::class));
        $inputName = 'foo';
        $api->allows('receive')->once()->with($inputName)->andThrow(new ChunkedResponseException());

        $this->assertInstanceOf(
            Response::class,
            $receiver->receive($inputName, function () {
            })
        );
    }

    public function testFactory(): void
    {
        $class = new ReflectionClass(Receiver::class);
        $property = $class->getProperty('api');
        $property->setAccessible(true);

        $receiver = Receiver::factory();
        self::assertInstanceOf(FileAPI::class, $property->getValue($receiver));

        $receiver = Receiver::factory([], 'FILEAPI');
        self::assertInstanceOf(FileAPI::class, $property->getValue($receiver));

        $receiver = Receiver::factory([], Plupload::class);
        self::assertInstanceOf(Plupload::class, $property->getValue($receiver));

        $receiver = Receiver::factory([], 'PLUPLOAD');
        self::assertInstanceOf(Plupload::class, $property->getValue($receiver));
    }
}
