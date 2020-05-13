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
     * @param $httpMethod
     * @param $route
     * @param $handler
     * @param string|null $name
     * @param array $middlewares
     */
    public function addRoute($httpMethod, $route, $handler, ?string $name = null, array $middlewares = []): void
    {
        $handler = $this->makeHandler($handler, $middlewares);
        $this->collector->map($httpMethod, $route, $handler, $name);
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
        if (!$this->reverser || !$this->isDirtyReverser) {
            $reverserClass = $this->reverserClass;
            $this->reverser = new $reverserClass($this->collector->getData());
        }
        return $this->reverser;
    }

    /**
     * @return Dispatcher
     */
    public function getDispatcher(): Dispatcher
    {
        if (!$this->dispatcher || !$this->isDirtyDispatcher) {
            $dispatcherClass = $this->dispatcherClass;
            $this->dispatcher = new $dispatcherClass($this->collector->getData());
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
                $this->invoker->invoke($request, $match[1], $params);
                break;
        }

        return $handler->handle($request);
    }
}