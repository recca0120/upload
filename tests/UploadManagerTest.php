<?php

namespace Recca0120\Upload\Tests;

use Illuminate\Config\Repository;
use Illuminate\Container\Container;
use Illuminate\Http\Request;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Mockery as m;
use PHPUnit\Framework\TestCase;
use Recca0120\Upload\Filesystem;
use Recca0120\Upload\Receiver;
use Recca0120\Upload\UploadManager;

class UploadManagerTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testCreateDefaultDriver(): void
    {
        $container = new Container();
        $container->instance('config', new Repository(['upload' => []]));

        $uploadManager = new UploadManager(
            $container,
            $request = m::mock(Request::class),
            m::mock(Filesystem::class)
        );
        $request->allows('root')->andReturn('foo');
        $this->assertInstanceOf(Receiver::class, $uploadManager->driver());
    }

    public function testCreatePluploadDriver(): void
    {
        $container = new Container();
        $container->instance('config', new Repository(['upload' => []]));

        $uploadManager = new UploadManager(
            $container,
            $request = m::mock(Request::class),
            m::mock(Filesystem::class)
        );
        $request->allows('root')->andReturn('foo');
        $this->assertInstanceOf(Receiver::class, $uploadManager->driver('plupload'));
    }

    public function testCreateFineUploaderDriver(): void
    {
        $container = new Container();
        $container->instance('config', new Repository(['upload' => []]));

        $uploadManager = new UploadManager(
            $container,
            $request = m::mock(Request::class),
            m::mock(Filesystem::class)
        );
        $request->allows('root')->andReturn('foo');
        $this->assertInstanceOf(Receiver::class, $uploadManager->driver('fineuploader'));
    }
}
