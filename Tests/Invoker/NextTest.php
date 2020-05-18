<?php declare(strict_types=1);

namespace Tests\Invoker;

use LogicException;
use Phact\Router\Invoker\Next;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Tests\Mocks\DummyMiddleware;

class NextTest extends TestCase
{
    public function testExceptionOnDuplicateCallHandle(): void
    {
        $this->expectException(LogicException::class);

        $request = $this->createMock(ServerRequestInterface::class);

        $response = $this->createMock(ResponseInterface::class);

        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler
            ->expects($this->once())
            ->method('handle')
            ->willReturn($response);

        $next = new Next(
            $handler,
            [
                new DummyMiddleware()
            ]
        );
        $next->handle($request);
        $next->handle($request);
    }
}
