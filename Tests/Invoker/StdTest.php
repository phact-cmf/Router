<?php declare(strict_types=1);

namespace Tests\Invoker;

use Tests\Mocks\DummyMiddleware;
use Tests\Mocks\DummyInvokableController;
use Tests\Mocks\DummyResponse;
use Phact\Router\Invoker\Std;
use Phact\Router\Route;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Tests\Mocks\DummyActionController;
use InvalidArgumentException;

class StdTest extends TestCase
{
    public function testCallHandlerIfInstanceOfPsrHandler(): void
    {
        $invoker = new Std();

        $request = $this->createMock(ServerRequestInterface::class);
        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler
            ->expects($this->once())
            ->method('handle');

        $invoker->invoke(
            $request,
            $handler,
            []
        );
    }

    public function testUnknownTypeOfHandler(): void
    {
        $invoker = new Std();

        $request = $this->createMock(ServerRequestInterface::class);

        $invoker->invoke(
            $request,
            function (ServerRequestInterface $serverRequest, array $vars) use ($request) {
                $this->assertEquals($request, $serverRequest);
                $this->assertEquals([
                    'test' => 'value'
                ], $vars);
                return $this->createMock(ResponseInterface::class);
            },
            [
                'test' => 'value'
            ]
        );
    }

    public function testProvidedCorrectVariablesAndRequestToOriginalHandler(): void
    {
        $invoker = new Std();

        $request = $this->createMock(ServerRequestInterface::class);
        $request
            ->method('withAttribute')
            ->willReturn(
                $this->returnSelf()
            );

        $emptyHandler = function (ServerRequestInterface $serverRequest, array $vars) use ($request) {
            $this->assertEquals($request, $serverRequest);
            $this->assertEquals([
                'test' => 'value'
            ], $vars);
            return $this->createMock(ResponseInterface::class);
        };

        $invoker->invoke(
            $request,
            new Route(
                $emptyHandler
            ),
            [
                'test' => 'value'
            ]
        );
    }

    public function testContainerUsedForResolveMiddleware(): void
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

        $invoker = new Std($container);

        $request = $this->createMock(ServerRequestInterface::class);
        $request
            ->method('withAttribute')
            ->willReturn(
                $this->returnSelf()
            );

        $emptyHandler = function () {
            return $this->createMock(ResponseInterface::class);
        };

        $invoker->invoke(
            $request,
            new Route(
                $emptyHandler,
                'someName',
                [
                    'simpleMiddleware'
                ]
            ),
            []
        );
    }

    public function testContainerUsedForResolveOriginalHandlerClass(): void
    {
        $classHandler = new class {
            public $response;

            public function __invoke()
            {
                return $this->response;
            }
        };

        $classHandler->response = $this->createMock(ResponseInterface::class);

        $container = $this->createMock(ContainerInterface::class);
        $container
            ->expects($this->once())
            ->method('has')
            ->with('SimpleClass')
            ->willReturn(true);

        $container
            ->expects($this->once())
            ->method('get')
            ->with('SimpleClass')
            ->willReturn(
                $classHandler
            );

        $invoker = new Std($container);

        $request = $this->createMock(ServerRequestInterface::class);
        $request
            ->method('withAttribute')
            ->willReturn(
                $this->returnSelf()
            );

        $invoker->invoke(
            $request,
            new Route(
                ['SimpleClass', '__invoke']
            ),
            []
        );
    }

    public function testArrayClassMethodHandler(): void
    {
        $handler = [DummyActionController::class, 'action'];

        $invoker = new Std();

        $request = $this->createMock(ServerRequestInterface::class);
        $request
            ->method('withAttribute')
            ->willReturn(
                $this->returnSelf()
            );

        $response = $invoker->invoke(
            $request,
            new Route(
                $handler
            ),
            []
        );
        $this->assertInstanceOf(DummyResponse::class, $response);
    }

    public function testDoubleColonHandler(): void
    {
        $handler = DummyActionController::class . '::action';

        $invoker = new Std();

        $request = $this->createMock(ServerRequestInterface::class);
        $request
            ->method('withAttribute')
            ->willReturn(
                $this->returnSelf()
            );

        $response = $invoker->invoke(
            $request,
            new Route(
                $handler
            ),
            []
        );
        $this->assertInstanceOf(DummyResponse::class, $response);
    }

    public function testArrayObjectMethodHandler(): void
    {
        $handler = [new DummyActionController(), 'action'];

        $invoker = new Std();

        $request = $this->createMock(ServerRequestInterface::class);
        $request
            ->method('withAttribute')
            ->willReturn(
                $this->returnSelf()
            );

        $response = $invoker->invoke(
            $request,
            new Route(
                $handler
            ),
            []
        );
        $this->assertInstanceOf(DummyResponse::class, $response);
    }

    public function testInvokableClassHandler(): void
    {
        $handler = DummyInvokableController::class;

        $invoker = new Std();

        $request = $this->createMock(ServerRequestInterface::class);
        $request
            ->method('withAttribute')
            ->willReturn(
                $this->returnSelf()
            );

        $response = $invoker->invoke(
            $request,
            new Route(
                $handler
            ),
            []
        );
        $this->assertInstanceOf(DummyResponse::class, $response);
    }

    public function testNonCallableHandlerCauseException(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $handler = 'NonCallableString';

        $invoker = new Std();

        $request = $this->createMock(ServerRequestInterface::class);
        $request
            ->method('withAttribute')
            ->willReturn(
                $this->returnSelf()
            );

        $invoker->invoke(
            $request,
            new Route(
                $handler
            ),
            []
        );
    }

    public function testClassMiddlewareCorrectlyWorksWithoutContainer(): void
    {
        $invoker = new Std();

        $request = $this->createMock(ServerRequestInterface::class);
        $request
            ->method('withAttribute')
            ->willReturn(
                $this->returnSelf()
            );

        $emptyHandler = function (ServerRequestInterface $request) {
            $this->assertEquals($request, $request);
            return $this->createMock(ResponseInterface::class);
        };

        $invoker->invoke(
            $request,
            new Route(
                $emptyHandler,
                'someName',
                [
                    DummyMiddleware::class
                ]
            ),
            []
        );
    }

    public function testObjectMiddlewareCorrectlyWorks(): void
    {
        $invoker = new Std();

        $request = $this->createMock(ServerRequestInterface::class);
        $request
            ->method('withAttribute')
            ->willReturn(
                $this->returnSelf()
            );

        $emptyHandler = function (ServerRequestInterface $request) {
            $this->assertEquals($request, $request);
            return $this->createMock(ResponseInterface::class);
        };

        $invoker->invoke(
            $request,
            new Route(
                $emptyHandler,
                'someName',
                [
                    new DummyMiddleware()
                ]
            ),
            []
        );
    }

    public function testExceptionOnIncorrectMiddleware(): void
    {

        $this->expectException(InvalidArgumentException::class);

        $invoker = new Std();

        $request = $this->createMock(ServerRequestInterface::class);
        $request
            ->method('withAttribute')
            ->willReturn(
                $this->returnSelf()
            );

        $emptyHandler = function (ServerRequestInterface $request) {
            return $this->createMock(ResponseInterface::class);
        };

        $invoker->invoke(
            $request,
            new Route(
                $emptyHandler,
                'someName',
                [
                    'InvalidMiddleware'
                ]
            ),
            []
        );
    }

    /**
     * Проверяем:
     * + Что в итоговый хэндлер приходят корректный request и корректные variables
     * + Использование контейнера при ресолве Middleware
     * + Использование контейнера при ресолве Класса
     * + Корректную отработку если вызов не с RouterHandler
     * Все типы хэндлера
     * Все типы мидлваров
     * Правильную последовательность вызова Middleware
     */
}
