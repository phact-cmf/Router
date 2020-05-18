<?php declare(strict_types=1);

namespace Tests;

use FastRoute\Dispatcher;
use Phact\Router\Exception\NotFoundException;
use Phact\Router\NotFoundHandler;
use Phact\Router\Router;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class RouterIntegrationTest extends TestCase
{
    public function testCorrectsAddRouteAndDispatch(): void
    {
        $router = new Router();
        $router->addRoute('GET', '/example', 'someHandler', 'index');
        $result = $router->dispatch('GET', '/example');
        $this->assertEquals([
            Dispatcher::FOUND,
            'someHandler',
            []
        ], $result);
    }

    public function testCorrectsAddRouteAndReverse(): void
    {
        $router = new Router();
        $router->addRoute('GET', '/example', 'someHandler', 'index');
        $result = $router->reverse('index');
        $this->assertEquals('/example', $result);
    }

    public function testCorrectsAddRouteGroupAndDispatch(): void
    {
        $router = new Router();
        $router->addGroup('/example', static function (Router $router) {
            $router->addRoute('GET', '/element', 'someHandler', 'index');
        }, 'example:');

        $result = $router->reverse('example:index');
        $this->assertEquals('/example/element', $result);
    }

    public function testCorrectsAddRouteGroupAndReverse(): void
    {
        $router = new Router();
        $router->addGroup('/example', static function (Router $router) {
            $router->addRoute('GET', '/element', 'someHandler', 'index');
        }, 'example:');

        $result = $router->dispatch('GET', '/example/element');
        $this->assertEquals([
            Dispatcher::FOUND,
            'someHandler',
            []
        ], $result);
    }

    public function testCorrectsMapWithParametersAndProcess(): void
    {
        $request = $this->generateRequest('GET', '/example/12');

        $handler = function (ServerRequestInterface $serverRequest, array $variables) use ($request) {
            $this->assertEquals($request, $serverRequest);
            $this->assertEquals([
                'id' => '12'
            ], $variables);
            return $this->createMock(ResponseInterface::class);
        };

        $router = new Router();
        $router->map(
            'GET',
            '/example/{id:[0-9]+}',
            $handler,
            'index'
        );

        $router->process(
            $request,
            new NotFoundHandler()
        );
    }

    public function testCorrectsMapWithParametersAndReverse(): void
    {
        $handler = function (ServerRequestInterface $serverRequest, array $variables) {
            return $this->createMock(ResponseInterface::class);
        };

        $router = new Router();
        $router->map(
            'GET',
            '/example/{id:[0-9]+}',
            $handler,
            'index'
        );

        $url = $router->reverse('index', [
            'id' => '12'
        ]);
        $this->assertEquals('/example/12', $url);
    }

    public function test404ExceptionOnNotFoundRoute(): void
    {
        $this->expectException(NotFoundException::class);

        $request = $this->generateRequest('GET', '/error');

        $handler = function (ServerRequestInterface $serverRequest, array $variables) use ($request) {
            return $this->createMock(ResponseInterface::class);
        };

        $router = new Router();
        $router->map(
            'GET',
            '/example/{id:[0-9]+}',
            $handler,
            'index'
        );

        $router->process(
            $request,
            new NotFoundHandler()
        );
    }

    public function testCorrectsGroupMapWithParametersAndProcess(): void
    {
        $request = $this->generateRequest('GET', '/example/12');

        $handler = function (ServerRequestInterface $serverRequest, array $variables) use ($request) {
            $this->assertEquals($request, $serverRequest);
            $this->assertEquals([
                'id' => '12'
            ], $variables);
            return $this->createMock(ResponseInterface::class);
        };

        $router = new Router();
        $router->group('/example', function (Router $router) use ($handler) {
            $router->map(
                'GET',
                '/{id:[0-9]+}',
                $handler,
                'index'
            );
        }, 'index:');

        $router->process(
            $request,
            new NotFoundHandler()
        );
    }

    public function testCorrectsGroupMapWithParametersAndReverse(): void
    {
        $handler = function (ServerRequestInterface $serverRequest, array $variables) {
            return $this->createMock(ResponseInterface::class);
        };

        $router = new Router();
        $router->group('/example', function (Router $router) use ($handler) {
            $router->map(
                'GET',
                '/{id:[0-9]+}',
                $handler,
                'index'
            );
        }, 'index:');

        $url = $router->reverse('index:index', [
            'id' => '12'
        ]);
        $this->assertEquals('/example/12', $url);
    }

    public function testContainerCorrectlyProvidedToInvoker(): void
    {
        $container = $this->createMock(ContainerInterface::class);
        $container
            ->expects($this->once())
            ->method('has')
            ->with('simpleMiddleware')
            ->willReturn(true);

        $container
            ->expects($this->once())
            ->method('get')
            ->with('simpleMiddleware')
            ->willReturnCallback(function () {
                $middleware = $this->createMock(MiddlewareInterface::class);
                $middleware
                    ->expects($this->once())
                    ->method('process')
                    ->willReturn(
                        $this->createMock(ResponseInterface::class)
                    );
                return $middleware;
            });

        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler
            ->expects($this->never())
            ->method('handle')
            ->willReturn(
                $this->createMock(ResponseInterface::class)
            );

        $router = new Router();
        $router->setContainer($container);
        $router->map(
            'GET',
            '/example',
            function () {
                return $this->createMock(ResponseInterface::class);
            },
            null,
            [
                'simpleMiddleware'
            ]
        );

        $router->process(
            $this->generateRequest(),
            $handler
        );
    }

    public function generateRequest(string $method = 'GET', string $path = '/example'): ServerRequestInterface
    {
        $request = $this->createMock(ServerRequestInterface::class);
        $request
            ->method('getMethod')
            ->willReturn($method);

        $request
            ->method('getUri')
            ->willReturnCallback(function () use ($path) {
                $uri = $this->createMock(UriInterface::class);
                $uri
                    ->method('getPath')
                    ->willReturn($path);
                return $uri;
            });

        $request
            ->method('withAttribute')
            ->willReturn(
                $this->returnSelf()
            );

        return  $request;
    }
}
