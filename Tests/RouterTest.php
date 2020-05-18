<?php declare(strict_types=1);

namespace Tests;

use FastRoute\Dispatcher;
use Phact\Router\Collector;
use Phact\Router\DispatcherFactory;
use Phact\Router\Exception\MethodNotAllowedException;
use Phact\Router\Invoker;
use Phact\Router\Loader;
use Phact\Router\Reverser;
use Phact\Router\ReverserFactory;
use Phact\Router\Route;
use Phact\Router\Router;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\SimpleCache\CacheInterface;

class RouterTest extends TestCase
{
    public function testAddedNotNamedRouteCorrectlyProvidedToCollector(): void
    {
        $collector = $this->createMock(Collector::class);
        $collector
            ->expects($this->once())
            ->method('map')
            ->with(
                'GET',
                '/example',
                'someHandler',
                null
            );
        $router = new Router($collector);
        $router->addRoute('GET', '/example', 'someHandler');
    }

    public function testAddedNamedRouteCorrectlyProvidedToCollector(): void
    {
        $collector = $this->createMock(Collector::class);
        $collector
            ->expects($this->once())
            ->method('map')
            ->with(
                'GET',
                '/example',
                'someHandler',
                'example'
            );
        $router = new Router($collector);
        $router->addRoute('GET', '/example', 'someHandler', 'example');
    }

    public function testAddedGroupCorrectlyProvidedToCollector(): void
    {
        $callable = static function (Router $router) {
        };
        $collector = $this->createMock(Collector::class);
        $collector
            ->expects($this->once())
            ->method('group')
            ->with(
                '/example',
                $callable,
                'example',
                $this->isInstanceOf(Router::class)
            );
        $router = new Router($collector);
        $router->addGroup('/example', $callable, 'example');
    }

    public function testMapCorrectlyProvidedToCollector(): void
    {
        $collector = $this->createMock(Collector::class);
        $collector
            ->expects($this->once())
            ->method('map')
            ->with(
                'GET',
                '/example',
                $this->isInstanceOf(Route::class),
                'name'
            );
        $router = new Router($collector);
        $router->map('GET', '/example', 'someHandler', 'name', ['testMiddleware']);
    }

    public function testMapCreatedRouteCorrectlyProvidedToCollector(): void
    {
        $collector = $this->createMock(Collector::class);
        $collector
            ->expects($this->once())
            ->method('map')
            ->with(
                'GET',
                '/example',
                $this->isInstanceOf(Route::class),
                'name'
            );
        $router = new Router($collector);
        $router->map('GET', '/example', new Route('someHandler'), 'name', ['testMiddleware']);
    }

    public function testMapGroupMiddlewaresCorrectlyProvidedToRoute(): void
    {
        $collector = $this->createMock(Collector::class);
        $collector
            ->expects($this->once())
            ->method('map')
            ->with(
                'GET',
                $this->isType('string'),
                $this->callback(function (Route $route) {
                    $this->assertEquals([
                        'commonMiddleware',
                        'groupMiddleware',
                        'mapMiddleware'
                    ], $route->getMiddlewares());
                    return true;
                }),
                $this->isType('string')
            );

        $collector
            ->method('group')
            ->willReturnCallback(function ($prefix, $callback, $name, $callbackScope) {
                $callback($callbackScope);
            });

        $router = new Router($collector);
        $router->setMiddlewares(['commonMiddleware']);
        $router->group('/example', static function (Router $router) {
            $router->map('GET', '/value', 'someHandler', 'name', ['mapMiddleware']);
        }, 'example:', ['groupMiddleware']);
    }

    public function testGroupCorrectlyProvidedToCollector(): void
    {
        $callable = static function (Router $router) {
        };
        $collector = $this->createMock(Collector::class);
        $collector
            ->expects($this->once())
            ->method('group')
            ->with(
                '/example',
                $callable,
                'example',
                $this->isInstanceOf(Router::class)
            );
        $router = new Router($collector);
        $router->group('/example', $callable, 'example', ['middleware1', 'middleware2']);
    }

    public function testDispatchProvidedToDispatcher(): void
    {
        $collector = $this->createMock(Collector::class);
        $dispatcher = $this->createMock(Dispatcher::class);
        $dispatcher
            ->expects($this->once())
            ->method('dispatch')
            ->willReturn([]);

        $dispatcherFabric = $this->createMock(DispatcherFactory::class);
        $dispatcherFabric
            ->expects($this->once())
            ->method('createDispatcher')
            ->willReturn($dispatcher);

        $router = new Router($collector, $dispatcherFabric);
        $router->dispatch('GET', '/example');
    }

    public function testDispatchWithNoChangesNotCreatesDispatcherTwice(): void
    {
        $collector = $this->createMock(Collector::class);
        $dispatcher = $this->createMock(Dispatcher::class);
        $dispatcher
            ->expects($this->exactly(2))
            ->method('dispatch')
            ->willReturn([]);

        $dispatcherFabric = $this->createMock(DispatcherFactory::class);
        $dispatcherFabric
            ->expects($this->once())
            ->method('createDispatcher')
            ->willReturn($dispatcher);

        $router = new Router($collector, $dispatcherFabric);
        $router->dispatch('GET', '/example');
        $router->dispatch('GET', '/example');
    }

    public function testReverseProvidedToReverser(): void
    {
        $collector = $this->createMock(Collector::class);
        $reverser = $this->createMock(Reverser::class);

        $reverser
            ->expects($this->once())
            ->method('reverse')
            ->willReturn('');

        $reverserFabric = $this->createMock(ReverserFactory::class);
        $reverserFabric
            ->expects($this->once())
            ->method('createReverser')
            ->willReturn($reverser);

        $router = new Router($collector, null, $reverserFabric);
        $router->reverse('some_name');
    }

    public function testUrlProvidedToReverser(): void
    {
        $collector = $this->createMock(Collector::class);
        $reverser = $this->createMock(Reverser::class);

        $reverser
            ->expects($this->once())
            ->method('reverse')
            ->willReturn('');

        $reverserFabric = $this->createMock(ReverserFactory::class);
        $reverserFabric
            ->expects($this->once())
            ->method('createReverser')
            ->willReturn($reverser);

        $router = new Router($collector, null, $reverserFabric);
        $router->url('some_name');
    }

    public function testProcessOnNotDefinedDispatchWillHandleProvidedHandler(): void
    {
        $collector = $this->createMock(Collector::class);

        $dispatcherFabric = $this->createMock(DispatcherFactory::class);
        $dispatcherFabric
            ->expects($this->once())
            ->method('createDispatcher')
            ->willReturnCallback(function () {
                $dispatcher = $this->createMock(Dispatcher::class);
                $dispatcher
                    ->expects($this->once())
                    ->method('dispatch')
                    ->willReturn([
                        3
                    ]);
                return $dispatcher;
            });

        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler
            ->expects($this->once())
            ->method('handle')
            ->willReturnCallback(function () {
                return $this->createMock(ResponseInterface::class);
            });

        $request = $this->createMock(ServerRequestInterface::class);
        $request
            ->method('getMethod')
            ->willReturn('GET');

        $request
            ->method('getUri')
            ->willReturnCallback(function () {
                $uri = $this->createMock(UriInterface::class);
                $uri
                    ->method('getPath')
                    ->willReturn('/example');
                return $uri;
            });


        $router = new Router($collector, $dispatcherFabric);
        $router->process($request, $handler);
    }

    public function testProcessOnNotFoundDispatchWillHandleProvidedHandler(): void
    {
        $collector = $this->createMock(Collector::class);

        $dispatcherFabric = $this->createMock(DispatcherFactory::class);
        $dispatcherFabric
            ->expects($this->once())
            ->method('createDispatcher')
            ->willReturnCallback(function () {
                $dispatcher = $this->createMock(Dispatcher::class);
                $dispatcher
                    ->expects($this->once())
                    ->method('dispatch')
                    ->willReturn([
                        Dispatcher::NOT_FOUND
                    ]);
                return $dispatcher;
            });

        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler
            ->expects($this->once())
            ->method('handle')
            ->willReturnCallback(function () {
                return $this->createMock(ResponseInterface::class);
            });

        $request = $this->createMock(ServerRequestInterface::class);
        $request
            ->method('getMethod')
            ->willReturn('GET');

        $request
            ->method('getUri')
            ->willReturnCallback(function () {
                $uri = $this->createMock(UriInterface::class);
                $uri
                    ->method('getPath')
                    ->willReturn('/example');
                return $uri;
            });


        $router = new Router($collector, $dispatcherFabric);
        $router->process($request, $handler);
    }

    public function testProcessOnNotAllowedThrowsException(): void
    {
        $collector = $this->createMock(Collector::class);

        $dispatcherFabric = $this->createMock(DispatcherFactory::class);
        $dispatcherFabric
            ->expects($this->once())
            ->method('createDispatcher')
            ->willReturnCallback(function () {
                $dispatcher = $this->createMock(Dispatcher::class);
                $dispatcher
                    ->expects($this->once())
                    ->method('dispatch')
                    ->willReturn([
                        Dispatcher::METHOD_NOT_ALLOWED,
                        ['PUT', 'DELETE']
                    ]);
                return $dispatcher;
            });

        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler
            ->expects($this->never())
            ->method('handle');

        $request = $this->createMock(ServerRequestInterface::class);
        $request
            ->method('getMethod')
            ->willReturn('GET');

        $request
            ->method('getUri')
            ->willReturnCallback(function () {
                $uri = $this->createMock(UriInterface::class);
                $uri
                    ->method('getPath')
                    ->willReturn('/example');
                return $uri;
            });

        $router = new Router($collector, $dispatcherFabric);
        try {
            $router->process($request, $handler);
        } catch (MethodNotAllowedException $exception) {
            $this->assertEquals(405, $exception->getStatusCode());
        }
    }

    public function testProcessOnFoundCallsInvokersInvoke(): void
    {
        $collector = $this->createMock(Collector::class);

        $dispatcherFabric = $this->createMock(DispatcherFactory::class);
        $dispatcherFabric
            ->expects($this->once())
            ->method('createDispatcher')
            ->willReturnCallback(function () {
                $dispatcher = $this->createMock(Dispatcher::class);
                $dispatcher
                    ->expects($this->once())
                    ->method('dispatch')
                    ->willReturn([
                        Dispatcher::FOUND,
                        'someHandler',
                        ['param1' => 'value']
                    ]);
                return $dispatcher;
            });

        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler
            ->expects($this->never())
            ->method('handle');

        $request = $this->createMock(ServerRequestInterface::class);
        $request
            ->method('getMethod')
            ->willReturn('GET');

        $request
            ->method('getUri')
            ->willReturnCallback(function () {
                $uri = $this->createMock(UriInterface::class);
                $uri
                    ->method('getPath')
                    ->willReturn('/example');
                return $uri;
            });

        $invoker = $this->createMock(Invoker::class);
        $invoker
            ->expects($this->once())
            ->method('invoke')
            ->with(
                $request,
                'someHandler',
                ['param1' => 'value']
            );

        $router = new Router($collector, $dispatcherFabric);
        $router->setInvoker($invoker);
        $router->process($request, $handler);
    }

    public function testUsedLoaderIfExists(): void
    {
        $loader = $this->createMock(Loader::class);
        $loader
            ->expects($this->atLeast(1))
            ->method('load');

        $router = new Router();
        $router->setLoader($loader);
        $router->dispatch('GET', '/example');
    }

    public function testLoaderLoadedOnce(): void
    {
        $loader = $this->createMock(Loader::class);
        $loader
            ->expects($this->once())
            ->method('load');

        $router = new Router();
        $router->setLoader($loader);
        $router->dispatch('GET', '/example');
        $router->dispatch('POST', '/examples');
        $router->map('GET', '/example', 'someHandler');
        $router->dispatch('GET', '/example');
    }

    public function testUsedCacheIfExists(): void
    {
        $cache = $this->createMock(CacheInterface::class);
        $cache
            ->method('has')
            ->with(
                'routes'
            )
            ->willReturn(true);

        $cache
            ->expects($this->once())
            ->method('get')
            ->with(
                'routes'
            )
            ->willReturn([
                [[],[]],
                []
            ]);

        $router = new Router();
        $router->setCache($cache);
        $router->dispatch('GET', '/example');
    }

    public function testSetToCache(): void
    {
        $cache = $this->createMock(CacheInterface::class);
        $cache
            ->method('has')
            ->with(
                'routes'
            )
            ->willReturn(false);

        $cache
            ->expects($this->once())
            ->method('set');

        $router = new Router();
        $router->setCache($cache);
        $router->dispatch('GET', '/example');
    }

    public function testCorrectlySetsNameAndTtl(): void
    {
        $cache = $this->createMock(CacheInterface::class);
        $cache
            ->method('has')
            ->with(
                'ROUTES_KEY'
            )
            ->willReturn(false);

        $cache
            ->expects($this->once())
            ->method('set')
            ->with(
                'ROUTES_KEY',
                $this->isType('array'),
                1200
            );

        $router = new Router();
        $router->setCache($cache);
        $router->setCacheKey('ROUTES_KEY');
        $router->setCacheTTL(1200);
        $router->dispatch('GET', '/example');
    }

    public function testNotUseLoaderIfCacheExists(): void
    {
        $cache = $this->createMock(CacheInterface::class);
        $cache
            ->method('has')
            ->with(
                'routes'
            )
            ->willReturn(true);

        $cache
            ->expects($this->once())
            ->method('get')
            ->with(
                'routes'
            )
            ->willReturn([
                [[],[]],
                []
            ]);

        $loader = $this->createMock(Loader::class);
        $loader
            ->expects($this->never())
            ->method('load');

        $router = new Router();
        $router->setCache($cache);
        $router->dispatch('GET', '/example');
    }
}
