<?php declare(strict_types=1);

namespace Phact\Router\Invoker;

use http\Exception\UnexpectedValueException;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Message\ResponseInterface;

class Next implements RequestHandlerInterface
{
    /**
     * @var RequestHandlerInterface
     */
    private $handler;

    /**
     * @var MiddlewareInterface[]
     */
    private $middlewareStack;

    /**
     * Next constructor.
     * @param RequestHandlerInterface $handler
     * @param array $middlewareStack
     */
    public function __construct(RequestHandlerInterface $handler, array $middlewareStack = [])
    {
        $this->handler = $handler;
        $this->middlewareStack = $middlewareStack;
    }

    public function handle(ServerRequestInterface $request) : ResponseInterface
    {
        if ($this->middlewareStack === null) {
            throw new UnexpectedValueException('Next handler already called');
        }

        if (empty($this->middlewareStack)) {
            return $this->handler->handle($request);
        }

        $middleware = array_shift($this->middlewareStack);
        $next = clone $this;
        $this->middlewareStack = null;

        return $middleware->process($request, $next);
    }
}