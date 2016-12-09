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
        $response = m::spy('Symfony\Component\HttpFoundation\Response');
        $name = 'test';

        /*
        |------------------------------------------------------------
        | Act
        |------------------------------------------------------------
        */

        $uploader
            ->shouldReceive('receive')->with($name)->andReturn($uploadedFile)
            ->shouldReceive('deleteUploadedFile')->andReturnSelf();

        $receiver = new Receiver($uploader);

        /*
        |------------------------------------------------------------
        | Assert
        |------------------------------------------------------------
        */

        $receiver->receive($name, function($uploaded) use ($response, $uploadedFile) {
            $this->assertSame($uploaded, $uploadedFile);

            return $response;
        });

        $uploader->shouldHaveReceived('receive')->with($name)->once();
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
        $name = 'test';

        /*
        |------------------------------------------------------------
        | Act
        |------------------------------------------------------------
        */

        $uploader
            ->shouldReceive('receive')->with($name)->andThrow($chunkedResponseException);

        $receiver = new Receiver($uploader);

        /*
        |------------------------------------------------------------
        | Assert
        |------------------------------------------------------------
        */

        $response = $receiver->receive($name, function() {});
        $this->assertInstanceOf('Symfony\Component\HttpFoundation\Response', $response);
        $uploader->shouldHaveReceived('receive')->with($name)->once();
    }
}
