<?php

namespace Recca0120\Upload\Tests\Drivers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\UploadedFile;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Mockery as m;
use PHPUnit\Framework\TestCase;
use Recca0120\Upload\Contracts\Api;
use Recca0120\Upload\Drivers\FileAPI;
use Recca0120\Upload\Drivers\Plupload;
use Recca0120\Upload\Exceptions\ChunkedResponseException;
use Recca0120\Upload\Receiver;
use ReflectionClass;
use Symfony\Component\HttpFoundation\Response;

class ReceiverTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testReceive(): void
    {
        $inputName = 'foo';
        $path = 'temp';
        $domain = 'https://foo.bar/';

        $uploadedFile = UploadedFile::fake()->create('中文.php');

        $api = m::spy(Api::class);
        $api->allows('path')->andReturn($path);
        $api->allows('domain')->andReturn($domain);
        $api->allows('receive')->with($inputName)->andReturn($uploadedFile)->once();
        $api->allows('clearTempDirectories')->andReturnSelf();
        $api->allows('completedResponse')->with(m::type(JsonResponse::class))->andReturnUsing(function ($response) {
            return $response;
        });

        $receiver = new Receiver($api);

        $response = $receiver->receive($inputName);

        $this->assertSame([
            'name' => $uploadedFile->getClientOriginalName(),
            'tmp_name' => $path.$uploadedFile->getBasename(),
            'type' => $uploadedFile->getMimeType(),
            'size' => $uploadedFile->getSize(),
            'url' => $domain.$path.$uploadedFile->getBasename(),
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
        $api->allows('clearTempDirectories')->once()->andReturnSelf();
        $api->allows('completedResponse')->once()->with($response)->andReturn($response);

        $callback = function () use ($response) {
            return $response;
        };

        $this->assertSame($response, $receiver->receive($inputName, $callback));
    }

    public function testReceiveAndThrowChunkedResponseException(): void
    {
        $receiver = new Receiver($api = m::mock(Api::class));
        $inputName = 'foo';
        $api->allows('receive')->once()->with($inputName)->andThrow(new ChunkedResponseException());
        $callback = function () {
            return new Response();
        };

        $this->assertInstanceOf(Response::class, $receiver->receive($inputName, $callback));
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
