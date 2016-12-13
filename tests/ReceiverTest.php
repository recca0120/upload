<?php

use Mockery as m;
use Recca0120\Upload\Receiver;
use Recca0120\Upload\Exceptions\ChunkedResponseException;

class ReceiverTest extends PHPUnit_Framework_TestCase
{
    public function tearDown()
    {
        m::close();
    }

    public function test_receive()
    {
        /*
        |------------------------------------------------------------
        | Arrange
        |------------------------------------------------------------
        */

        $uploader = m::spy('Recca0120\Upload\Contracts\Uploader');
        $uploadedFile = m::spy('Symfony\Component\HttpFoundation\File\UploadedFile');
        $response = m::spy('Illuminate\Http\JsonResponse');
        $inputName = 'test';
        $config = [];

        /*
        |------------------------------------------------------------
        | Act
        |------------------------------------------------------------
        */

        $uploader
            ->shouldReceive('getConfig')->andReturn($config)
            ->shouldReceive('makeDirectory')->with(sys_get_temp_dir().'/storage/temp')->andReturnSelf()
            ->shouldReceive('receive')->with($inputName)->andReturn($uploadedFile)
            ->shouldReceive('deleteUploadedFile')->andReturnSelf();

        $receiver = new Receiver($uploader);

        /*
        |------------------------------------------------------------
        | Assert
        |------------------------------------------------------------
        */

        $receiver->receive($inputName, function ($uploaded) use ($response, $uploadedFile) {
            $this->assertSame($uploaded, $uploadedFile);

            return $response;
        });

        $uploader->shouldHaveReceived('getConfig')->once();
        $uploader->shouldHaveReceived('makeDirectory')->with(sys_get_temp_dir().'/storage/temp')->once();
        $uploader->shouldHaveReceived('receive')->with($inputName)->once();
        $uploader->shouldHaveReceived('deleteUploadedFile')->with($uploadedFile)->once();
        $uploader->shouldHaveReceived('completedResponse')->with($response)->once();
    }

    public function test_receive_and_throw_chunked_response_exception()
    {
        /*
        |------------------------------------------------------------
        | Arrange
        |------------------------------------------------------------
        */

        $uploader = m::spy('Recca0120\Upload\Contracts\Uploader');
        $chunkedResponseException = new ChunkedResponseException();
        $inputName = 'test';
        $config = [];

        /*
        |------------------------------------------------------------
        | Act
        |------------------------------------------------------------
        */

        $uploader
            ->shouldReceive('getConfig')->andReturn($config)
            ->shouldReceive('makeDirectory')->with(sys_get_temp_dir().'/storage/temp')->andReturnSelf()
            ->shouldReceive('receive')->with($inputName)->andThrow($chunkedResponseException);

        $receiver = new Receiver($uploader);

        /*
        |------------------------------------------------------------
        | Assert
        |------------------------------------------------------------
        */

        $response = $receiver->receive($inputName, function () {
        });
        $this->assertInstanceOf('Symfony\Component\HttpFoundation\Response', $response);
        $uploader->shouldHaveReceived('getConfig')->once();
        $uploader->shouldHaveReceived('makeDirectory')->with(sys_get_temp_dir().'/storage/temp')->once();
        $uploader->shouldHaveReceived('receive')->with($inputName)->once();
    }

    public function test_save()
    {
        /*
        |------------------------------------------------------------
        | Arrange
        |------------------------------------------------------------
        */

        $uploader = m::spy('Recca0120\Upload\Contracts\Uploader');
        $uploadedFile = m::spy('Symfony\Component\HttpFoundation\File\UploadedFile');
        $response = m::spy('Illuminate\Http\JsonResponse');
        $inputName = 'test';
        $destination = 'destination';
        $basePath = 'base_path';
        $baseUrl = 'base_url';
        $config = [
            'base_path' => $basePath,
            'base_url' => $baseUrl,
        ];

        $clientOriginalName = 'client_original_name.PHP';
        $clientOriginalExtension = 'PHP';
        $basename = 'client_original_name';
        $mimeType = 'mimetype';
        $size = 100;

        /*
        |------------------------------------------------------------
        | Act
        |------------------------------------------------------------
        */

        $uploader
            ->shouldReceive('getConfig')->andReturn($config)
            ->shouldReceive('makeDirectory')->with($basePath.'/'.$destination)->andReturnSelf()
            ->shouldReceive('receive')->with($inputName)->andReturn($uploadedFile)
            ->shouldReceive('deleteUploadedFile')->andReturnSelf()
            ->shouldReceive('completedResponse')->with(m::type('Illuminate\Http\JsonResponse'))->andReturn($response);

        $uploadedFile
            ->shouldReceive('getClientOriginalName')->andReturn($clientOriginalName)
            ->shouldReceive('getClientOriginalExtension')->andReturn($clientOriginalExtension)
            ->shouldReceive('getBasename')->andReturn($clientOriginalName)
            ->shouldReceive('getMimeType')->andReturn($mimeType)
            ->shouldReceive('getSize')->andReturn($size)
            ->shouldReceive('move')->with($basePath.'/'.$destination, $basename.'.'.$clientOriginalExtension);

        $receiver = new Receiver($uploader, $config);

        /*
        |------------------------------------------------------------
        | Assert
        |------------------------------------------------------------
        */

        $this->assertSame($response, $receiver->save($inputName, $destination));
        $uploader->shouldHaveReceived('getConfig')->once();
        $uploader->shouldHaveReceived('makeDirectory')->with($basePath.'/'.$destination)->once();
        $uploader->shouldHaveReceived('receive')->with($inputName)->once();
        $uploadedFile->shouldReceive('getClientOriginalName')->andReturn($clientOriginalName);
        $uploadedFile->shouldReceive('getClientOriginalExtension')->andReturn($clientOriginalExtension);
        $uploadedFile->shouldReceive('getBasename')->andReturn($clientOriginalName);
        $uploadedFile->shouldReceive('getMimeType')->andReturn($mimeType);
        $uploadedFile->shouldReceive('getSize')->andReturn($size);
        $uploadedFile->shouldReceive('move')->with($basePath.'/'.$destination, $basename.'.'.$clientOriginalExtension);
        $uploader->shouldHaveReceived('deleteUploadedFile')->with($uploadedFile)->once();
        $uploader->shouldHaveReceived('completedResponse')->with(m::on(function ($response) use ($clientOriginalName, $mimeType, $size) {
            $data = $response->getData();
            $this->assertSame($data->name, $clientOriginalName);
            $this->assertSame($data->type, $mimeType);
            $this->assertSame($data->size, $size);

            return is_a($response, 'Illuminate\Http\JsonResponse');
        }))->once();
    }

    public function test_factory_default()
    {
        /*
        |------------------------------------------------------------
        | Arrange
        |------------------------------------------------------------
        */

        $config = [];
        $class = 'Recca0120\Upload\Uploaders\FileAPI';

        /*
        |------------------------------------------------------------
        | Act
        |------------------------------------------------------------
        */

        /*
        |------------------------------------------------------------
        | Assert
        |------------------------------------------------------------
        */

        $this->assertAttributeInstanceOf($class, 'uploader', Receiver::factory($config));
    }

    public function test_factory_fileapi()
    {
        /*
        |------------------------------------------------------------
        | Arrange
        |------------------------------------------------------------
        */

        $config = [];
        $classes = [
            'Recca0120\Upload\Uploaders\FileAPI' => 'Recca0120\Upload\Uploaders\FileAPI',
            'filapi' => 'Recca0120\Upload\Uploaders\FileAPI',
        ];

        /*
        |------------------------------------------------------------
        | Act
        |------------------------------------------------------------
        */

        /*
        |------------------------------------------------------------
        | Assert
        |------------------------------------------------------------
        */

        foreach ($classes as $class) {
            $this->assertAttributeInstanceOf($class, 'uploader', Receiver::factory($config, $class));
        }
    }

    public function test_factory_plupload()
    {
        /*
        |------------------------------------------------------------
        | Arrange
        |------------------------------------------------------------
        */

        $config = [];
        $classes = [
            'Recca0120\Upload\Uploaders\Plupload' => 'Recca0120\Upload\Uploaders\FileAPI',
            'plupload' => 'Recca0120\Upload\Uploaders\Plupload',
        ];

        /*
        |------------------------------------------------------------
        | Act
        |------------------------------------------------------------
        */

        /*
        |------------------------------------------------------------
        | Assert
        |------------------------------------------------------------
        */

        foreach ($classes as $class) {
            $this->assertAttributeInstanceOf($class, 'uploader', Receiver::factory($config, $class));
        }
    }
}
