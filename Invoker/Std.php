<?php declare(strict_types=1);
/**
 *
 *
 * All rights reserved.
 *
 * @author Okulov Anton
 * @email qantus@mail.ru
 * @version 1.0
 * @date 13/05/2020 08:39
 */

namespace Phact\Router\Invoker;


use InvalidArgumentException;
use Phact\Router\Invoker;
use Phact\Router\RouterHandler;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class Std implements Invoker
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
    public function invoke(ServerRequestInterface $request, $handler, array $middlewares, array $variables): ResponseInterface
    {
        if ($handler instanceof RequestHandlerInterface) {
            return $handler->handle($request);
        }
        if ($handler instanceof RouterHandler) {
            $middlewaresResolved = [];
            foreach ($handler->getMiddlewares() as $middleware) {
                $middlewaresResolved[] = $this->resolveMiddleware($middleware);
            }
            if ($handler->getName()) {
                $request = $request->withAttribute('routeName', $handler->getName());
            }
            $requestHandler = $this->processHandler($request, $handler->getOriginalHandler(), $variables);
            return (new Next($requestHandler, $middlewaresResolved))->handle($request);
        }
        return $this->processHandler($request, $handler, $variables)->handle($request);
    }

    public function processHandler(ServerRequestInterface $request, $handler, array $variables): RequestHandlerInterface
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
        if (is_string($callable) && strpos($callable, '::') !== false) {
            $callable = explode('::', $callable);
        }

        if (is_array($callable) && isset($callable[0]) && is_object($callable[0])) {
            $callable = [$callable[0], $callable[1]];
        }

        if (is_array($callable) && isset($callable[0]) && is_string($callable[0])) {
            $callable = [$this->resolveClass($callable[0]), $callable[1]];
        }

        if (is_string($callable) && method_exists($callable, '__invoke')) {
            $callable = $this->resolveClass($callable);
        }

        if (!is_callable($callable)) {
            throw new InvalidArgumentException('Could not resolve a callable for this route');
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
        if ($this->container === null && is_string($middleware) && class_exists($middleware)) {
            $middleware = new $middleware;
        }

        if ($this->container !== null && is_string($middleware) && $this->container->has($middleware)) {
            $middleware = $this->container->get($middleware);
        }

        if ($middleware instanceof MiddlewareInterface) {
            return $middleware;
        }

        throw new InvalidArgumentException(sprintf('Could not resolve middleware class: %s', $middleware));
    }
}