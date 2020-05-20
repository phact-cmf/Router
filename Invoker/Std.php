<?php declare(strict_types=1);

namespace Phact\Router\Invoker;

use InvalidArgumentException;
use Phact\Router\Invoker;
use Phact\Router\RouterHandler;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class Std implements Invoker, HandlerProcessorInterface
{
    /**
     * @var ContainerInterface|null
     */
    private $container;

    public function __construct(?ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    /**
     * @inheritDoc
     */
    public function invoke(ServerRequestInterface $request, $handler, array $variables): ResponseInterface
    {
        if ($handler instanceof RequestHandlerInterface) {
            return $handler->handle($request);
        }
        if ($handler instanceof RouterHandler) {
            $middlewaresResolved = [];
            foreach ($handler->getMiddlewares() as $middleware) {
                $middlewaresResolved[] = $this->resolveMiddleware($middleware);
            }
            $request = $request->withAttribute('router:variables', $variables);
            if ($handler->getName()) {
                $request = $request->withAttribute('router:name', $handler->getName());
            }
            $requestHandler = new HandlerProcessorAdapter($this, $handler->getOriginalHandler(), $variables);
            return (new Next($requestHandler, $middlewaresResolved))->handle($request);
        }
        return $this->processHandler($request, $handler, $variables);
    }

    public function processHandler(ServerRequestInterface $request, $handler, array $variables): ResponseInterface
    {
        $controller = $this->getCallable($handler);
        return $controller($request, $variables);
    }

    /**
     * Get the controller callable
     *
     * @param $callable callable|string|array|object
     *
     * @return callable
     */
    public function getCallable($callable)
    {
        if (is_string($callable)) {
            $callable = $this->getCallableFromString($callable);
        }

        if (is_array($callable)) {
            $callable = $this->getCallableFromArray($callable);
        }

        if (!is_callable($callable)) {
            throw new InvalidArgumentException('Could not resolve a callable for this route');
        }

        return $callable;
    }

    /**
     * Get callable from string
     *
     * @param string $callable
     *
     * @return callable|object|array
     */
    public function getCallableFromString(string $callable)
    {
        if (strpos($callable, '::') !== false) {
            return explode('::', $callable);
        }
        if (method_exists($callable, '__invoke')) {
            return $this->resolveClass($callable);
        }
        return $callable;
    }

    /**
     * Get callable from string
     *
     * @param array $callable
     *
     * @return callable
     */
    public function getCallableFromArray(array $callable): callable
    {
        if (!isset($callable[0])) {
            return $callable;
        }
        if (is_object($callable[0])) {
            return [$callable[0], $callable[1]];
        }
        if (is_string($callable[0])) {
            return [$this->resolveClass($callable[0]), $callable[1]];
        }
        return $callable;
    }

    /**
     * Get an object instance from a class name
     *
     * @param string $class
     *
     * @return object
     */
    protected function resolveClass(string $class)
    {
        if ($this->container instanceof ContainerInterface && $this->container->has($class)) {
            return $this->container->get($class);
        }

        return new $class();
    }

    /**
     * Resolve a middleware implementation, optionally from a container
     *
     * @param MiddlewareInterface|string $middleware
     *
     * @return MiddlewareInterface
     */
    protected function resolveMiddleware($middleware): MiddlewareInterface
    {
        $middleware = $this->resolveMiddlewareWithContainer($middleware);
        $middleware = $this->resolveMiddlewareWithoutContainer($middleware);

        if ($middleware instanceof MiddlewareInterface) {
            return $middleware;
        }

        throw new InvalidArgumentException(sprintf('Could not resolve middleware class: %s', (string) $middleware));
    }

    /**
     * Resolve middleware without container
     *
     * @param MiddlewareInterface|string $middleware
     *
     * @return MiddlewareInterface
     */
    protected function resolveMiddlewareWithoutContainer($middleware)
    {
        if ($this->container === null && is_string($middleware) && class_exists($middleware)) {
            $middleware = new $middleware;
        }
        return $middleware;
    }

    /**
     * Resolve middleware with container
     *
     * @param MiddlewareInterface|string $middleware
     *
     * @return MiddlewareInterface
     */
    protected function resolveMiddlewareWithContainer($middleware)
    {
        if ($this->container !== null && is_string($middleware) && $this->container->has($middleware)) {
            $middleware = $this->container->get($middleware);
        }
        return $middleware;
    }
}
