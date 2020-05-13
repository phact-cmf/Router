<?php declare(strict_types=1);
/**
 *
 *
 * All rights reserved.
 *
 * @author Okulov Anton
 * @email qantus@mail.ru
 * @version 1.0
 * @date 12/05/2020 10:03
 */

namespace Phact\Router;

use FastRoute\Dispatcher;
use FastRoute\RouteCollector;
use HttpException;
use InvalidArgumentException;
use Phact\Router\ReverserDataGenerator\Std;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Container\ContainerInterface;

class FastRouter implements MiddlewareInterface
{
    /**
     * @var Collector
     */
    protected $collector;

    /**
     * @var string
     */
    protected $dispatcherClass;

    /**
     * @var string
     */
    protected $reverserClass;

    /**
     * @var Dispatcher
     */
    protected $dispatcher;

    /**
     * @var Reverser
     */
    protected $reverser;

    /**
     * @var ContainerInterface|null
     */
    protected $container;

    public function __construct(?Collector $collector = null, ?string $dispatcherClass = null, ?string $reverserClass = null, ?ContainerInterface $container = null)
    {
        if ($collector === null) {
            $collector = new Collector(
                new \FastRoute\RouteParser\Std(),
                new \FastRoute\DataGenerator\GroupCountBased(),
                new Std()
            );
        }
        $this->collector = $collector;

        if ($dispatcherClass === null) {
            $dispatcherClass = \FastRoute\Dispatcher\GroupCountBased::class;
        }
        $this->dispatcherClass = $dispatcherClass;

        if ($reverserClass === null) {
            $reverserClass = \Phact\Router\Reverser\Std::class;
        }
        $this->reverserClass = $reverserClass;

        $this->container = $container;
    }

    /**
     * @param string $name
     * @param string|string[] $httpMethod
     * @param string $route
     * @param mixed $handler
     * @param array $middlewares
     */
    public function addNamedRoute(string $name, $httpMethod, $route, $handler, array $middlewares = []): void
    {
        $handler = $this->makeHandler($handler, $middlewares, $name);
        $this->collector->addNamedRoute($name, $httpMethod, $route, $handler);
    }

    /**
     * @param $httpMethod
     * @param $route
     * @param $handler
     * @param array $middlewares
     */
    public function addRoute($httpMethod, $route, $handler, array $middlewares = []): void
    {
        $handler = $this->makeHandler($handler, $middlewares);
        $this->collector->addRoute($httpMethod, $route, $handler);
    }

    /**
     * Create handler wrapper
     *
     * @param mixed $handler
     * @param MiddlewareInterface[] $middlewares
     * @param string|null $name
     * @return RouterHandler
     */
    public function makeHandler($handler, array $middlewares = [], ?string $name = null): RouterHandler
    {
        if ($handler instanceof RouterHandler) {
            return $handler;
        }
        return new Handler($handler, $middlewares, $name);
    }

    /**
     * @return RouteCollector
     */
    public function getCollector(): RouteCollector
    {
        return $this->collector;
    }

    /**
     * @return Reverser
     */
    public function getReverser(): Reverser
    {
        $reverserClass = $this->reverserClass;
        return new $reverserClass($this->collector->getData());
    }

    /**
     * @return Dispatcher
     */
    public function getDispatcher(): Dispatcher
    {
        $dispatcherClass = $this->dispatcherClass;
        return new $dispatcherClass($this->collector->getData());
    }

    /**
     * @param string $routeName
     * @param array $variables
     * @return string
     */
    public function reverse(string $routeName, array $variables = []): string
    {
        if ($this->reverser === null) {
            $this->reverser = $this->getReverser();
        }
        return $this->reverser->reverse($routeName, $variables);
    }

    /**
     * Dispatches against the provided HTTP method verb and URI.
     *
     * Returns array with one of the following formats:
     *
     *     [Dispatcher::NOT_FOUND]
     *     [Dispatcher::METHOD_NOT_ALLOWED, ['GET', 'OTHER_ALLOWED_METHODS']]
     *     [Dispatcher::FOUND, $handler, ['varName' => 'value', ...]]
     *
     * @param string $httpMethod
     * @param string $uri
     *
     * @return array
     */
    public function dispatch(string $httpMethod, string $uri): array
    {
        if ($this->dispatcher === null) {
            $this->dispatcher = $this->getDispatcher();
        }

        return $this->dispatcher->dispatch($httpMethod, $uri);
    }

    /**
     * @inheritDoc
     *
     * @throws HttpException
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $httpMethod = $request->getMethod();
        $uri = $request->getUri()->getPath();

        $match = $this->dispatch($httpMethod, $uri);

        switch ($match[0]) {
            case Dispatcher::NOT_FOUND:
                break;
            case Dispatcher::METHOD_NOT_ALLOWED:
                throw new HttpException('Method not allowed', 405);
                break;
            case Dispatcher::FOUND:
                $params = $match[2];
                $request->withAttribute('routeParams', $params);
                $this->resolve($request, $match[1], $params);
                break;
        }

        return $handler->handle($request);
    }

    public function resolve(ServerRequestInterface $request, $handler, array $variables): ResponseInterface
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