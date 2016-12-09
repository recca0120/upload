<?php

use Mockery as m;
use Recca0120\Upload\UploadManager;

class UploadManagerTest extends PHPUnit_Framework_TestCase
{
    public function tearDown()
    {
        m::close();
    }

    public function test_create_default_driver()
    {
        /*
        |------------------------------------------------------------
        | Arrange
        |------------------------------------------------------------
        */

        $app = [
            'config' => [
                'upload' => [],
            ],
        ];

        /*
        |------------------------------------------------------------
        | Act
        |------------------------------------------------------------
        */

        $uploadManager = new UploadManager($app);

        /*
        |------------------------------------------------------------
        | Assert
        |------------------------------------------------------------
        */

        $this->assertInstanceOf('Recca0120\Upload\Receiver', $uploadManager->driver());
    }

    public function test_create_plupload_driver()
    {
        /*
        |------------------------------------------------------------
        | Arrange
        |------------------------------------------------------------
        */

        $app = [
            'config' => [
                'upload' => [],
            ],
        ];

        /*
        |------------------------------------------------------------
        | Act
        |------------------------------------------------------------
        */

        $uploadManager = new UploadManager($app);

        /*
        |------------------------------------------------------------
        | Assert
        |------------------------------------------------------------
        */

        $this->assertInstanceOf('Recca0120\Upload\Receiver', $uploadManager->driver('plupload'));
    }
}
