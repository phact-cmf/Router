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
use Phact\Router\ReverserDataGenerator\Std;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Container\ContainerInterface;

class Router implements MiddlewareInterface
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
     * @var Invoker
     */
    protected $invoker;

    /**
     * Is new generation of dispatcher needed
     *
     * @var bool
     */
    protected $isDirtyDispatcher = true;

    /**
     * Is new generation of reverser needed
     *
     * @var bool
     */
    protected $isDirtyReverser = true;

    /**
     * @var array
     */
    protected $currentMiddlewares = [];

    public function __construct(?Collector $collector = null, ?string $dispatcherClass = null, ?string $reverserClass = null, ?ContainerInterface $container = null, ?Invoker $invoker = null)
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

        if (!$invoker) {
            $invoker = new \Phact\Router\Invoker\Std($container);
        }
        $this->invoker = $invoker;
    }

    /**
     * Set common middlewares
     *
     * @param array $middlewares
     */
    public function setMiddlewares(array $middlewares): void
    {
        $this->currentMiddlewares = $middlewares;
    }

    protected function setIsDirty(): void
    {
        $this->isDirtyDispatcher = true;
        $this->isDirtyReverser = true;
    }

    /**
     * @param $httpMethod
     * @param $route
     * @param $handler
     * @param string|null $name
     */
    public function addRoute($httpMethod, $route, $handler, ?string $name = null): void
    {
        $this->collector->map($httpMethod, $route, $handler, $name);
        $this->setIsDirty();
    }

    /**
     * @param $prefix
     * @param callable $callback
     * @param string|null $name
     */
    public function addGroup($prefix, callable $callback, ?string $name = null): void
    {
        $this->collector->group($prefix, $callback, $name);
    }

    /**
     * @param $httpMethod
     * @param $route
     * @param $handler
     * @param string|null $name
     * @param array $middlewares
     */
    public function map($httpMethod, $route, $handler, ?string $name = null, array $middlewares = []): void
    {
        $middlewares = array_merge($this->currentMiddlewares, $middlewares);
        $routeHandler = $this->createRoute($handler, $name, $middlewares);
        $this->collector->map($httpMethod, $route, $routeHandler, $name);
        $this->setIsDirty();
    }

    /**
     * @param $prefix
     * @param callable $callback
     * @param string|null $name
     * @param array $middlewares
     */
    public function group($prefix, callable $callback, ?string $name = null, array $middlewares = []): void
    {
        $previousMiddlewares = $this->currentMiddlewares;
        $this->currentMiddlewares = array_merge($previousMiddlewares, $middlewares);
        $this->collector->group($prefix, $callback, $name);
        $this->currentMiddlewares = $previousMiddlewares;
    }

    /**
     * Create handler wrapper
     *
     * @param mixed $handler
     * @param MiddlewareInterface[] $middlewares
     * @param string|null $name
     * @return RouterHandler
     */
    public function createRoute($handler, ?string $name = null, array $middlewares = []): RouterHandler
    {
        if ($handler instanceof RouterHandler) {
            return $handler;
        }
        return new Route($handler, $name, $middlewares);
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
        if (!$this->reverser || $this->isDirtyReverser) {
            $reverserClass = $this->reverserClass;
            $this->reverser = new $reverserClass($this->collector->getData());
            $this->isDirtyReverser = false;
        }
        return $this->reverser;
    }

    /**
     * @return Dispatcher
     */
    public function getDispatcher(): Dispatcher
    {
        if (!$this->dispatcher || $this->isDirtyDispatcher) {
            $dispatcherClass = $this->dispatcherClass;
            $this->dispatcher = new $dispatcherClass($this->collector->getData());
            $this->isDirtyDispatcher = false;
        }
        return $this->dispatcher;
    }

    /**
     * @param string $routeName
     * @param array $variables
     * @return string
     */
    public function reverse(string $routeName, array $variables = []): string
    {
        return $this->getReverser()->reverse($routeName, $variables);
    }

    /**
     * Alias for reverse
     *
     * @param string $routeName
     * @param array $variables
     * @return string
     */
    public function url(string $routeName, array $variables = []): string
    {
        return $this->reverse($routeName, $variables);
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
        return $this->getDispatcher()->dispatch($httpMethod, $uri);
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
                $this->invoker->invoke($request, $match[1], $params);
                break;
        }

        return $handler->handle($request);
    }
}