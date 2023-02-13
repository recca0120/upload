<?php

namespace Recca0120\Upload\Tests;

use Illuminate\Config\Repository;
use Illuminate\Container\Container;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Http\Request;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Mockery as m;
use PHPUnit\Framework\TestCase;
use Recca0120\Upload\UploadManager;
use Recca0120\Upload\UploadServiceProvider;

class UploadServiceProviderTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    protected function setUp(): void
    {
        parent::setUp();
        if (class_exists(Container::class)) {
            $container = new Container();
            $container->instance('path.storage', __DIR__);
            $container->instance('path.public', __DIR__);
            $container->instance('path.config', __DIR__);
            Container::setInstance($container);
        }
    }

    /**
     * @throws BindingResolutionException
     */
    public function testRegister(): void
    {
        $app = m::mock(new Container());
        $app->instance('request', Request::capture());
        $config = new Repository(['upload' => []]);
        $app->instance('config', $config);

        $serviceProvider = new UploadServiceProvider($app);

        $serviceProvider->register();

        self::assertInstanceOf(UploadManager::class, $app->make(UploadManager::class));
    }

    public function testBoot(): void
    {
        $app = m::mock(new Container());
        $app->instance('request', Request::capture());
        $config = new Repository(['upload' => []]);
        $app->instance('config', $config);
        $app->allows('runningInConsole')->once()->andReturn(true);

        $serviceProvider = new UploadServiceProvider($app);
        $serviceProvider->boot();
        if (method_exists(UploadServiceProvider::class, 'publishableProviders') === true) {
            $this->assertNotEmpty(UploadServiceProvider::publishableProviders());
        } else {
            $this->assertAttributeNotEmpty('publishes', $serviceProvider);
        }
    }
}
