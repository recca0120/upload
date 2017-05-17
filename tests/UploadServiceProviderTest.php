<?php

namespace Recca0120\Upload\Tests;

use Mockery as m;
use PHPUnit\Framework\TestCase;
use Illuminate\Container\Container;
use Recca0120\Upload\UploadServiceProvider;

class UploadServiceProviderTest extends TestCase
{
    protected function setUp()
    {
        parent::setUp();
        if (class_exists(Container::class)) {
            $container = Container::getInstance();
            $container->instance('path.storage', __DIR__);
            $container->instance('path.public', __DIR__);
            $container->instance('path.config', __DIR__);
        }
    }

    protected function tearDown()
    {
        parent::tearDown();
        m::close();
    }

    public function testRegister()
    {
        $serviceProvider = new UploadServiceProvider(
            $app = m::mock('Illuminate\Contracts\Foundation\Application, ArrayAccess')
        );
        $app->shouldReceive('offsetGet')->twice()->with('config')->andReturn(
            $config = m::mock('Illuminate\Contracts\Config\Repository, ArrayAccess')
        );
        $config->shouldReceive('get')->once()->with('upload', [])->andReturn([]);
        $config->shouldReceive('set')->once()->with('upload', m::type('array'));

        $app->shouldReceive('singleton')->once()->with(
            'Recca0120\Upload\Filesystem', 'Recca0120\Upload\Filesystem'
        );
        $app->shouldReceive('singleton')->once()->with(
            'Recca0120\Upload\UploadManager', m::on(function ($closure) use ($app) {
                $app->shouldReceive('offsetGet')->once()->with('request')->andReturn(
                    $request = m::mock('Illuminate\Http\Request')
                );
                $app->shouldReceive('make')->once()->with(
                    'Recca0120\Upload\Filesystem'
                )->andReturn(
                    m::mock('Recca0120\Upload\Filesystem')
                );
                $this->assertInstanceOf('Recca0120\Upload\UploadManager', $closure($app));

                return true;
            })
        );
        $serviceProvider->register();
    }

    public function testBoot()
    {
        $serviceProvider = new UploadServiceProvider(
            $app = m::mock('Illuminate\Contracts\Foundation\Application, ArrayAccess')
        );
        $app->shouldReceive('runningInConsole')->once()->andReturn(true);
        $serviceProvider->boot();
        $this->assertAttributeNotEmpty('publishes', $serviceProvider);
    }
}
