<?php declare(strict_types=1);

namespace Phact\Router;

use FastRoute\Dispatcher;
use Phact\Router\Exception\{HttpException, MethodNotAllowedException};
use Phact\Router\Invoker\{InvokerAwareInterface, InvokerAwareTrait};
use Phact\Router\Loader\{LoaderAwareInterface, LoaderAwareTrait};
use Phact\Router\ReverserDataGenerator\Std;
use Psr\SimpleCache\InvalidArgumentException;
use Psr\Http\Message\{ResponseInterface, ServerRequestInterface};
use Psr\Http\Server\{MiddlewareInterface, RequestHandlerInterface};
use Psr\Container\ContainerInterface;

class Router implements
    MiddlewareInterface,
    RouteCollector,
    LoaderAwareInterface,
    CacheAwareInterface,
    InvokerAwareInterface,
    ContainerAwareInterface
{
    use LoaderAwareTrait;
    use CacheAwareTrait;
    use ContainerAwareTrait;
    use InvokerAwareTrait;

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
     * Is new generation of reverser and dispatcher needed
     *
     * @var bool
     */
    protected $isDirty = true;

    /**
     * Is loaded data from Loader or cache
     *
     * @var bool
     */
    protected $isLoaded = false;

    /**
     * @var array
     */
    protected $currentMiddlewares = [];

    public function __construct(?Collector $collector = null, ?string $dispatcherClass = null, ?string $reverserClass = null)
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

    /**
     * @inheritDoc
     */
    public function addRoute($httpMethod, $route, $handler, ?string $name = null): void
    {
        $this->collector->map($httpMethod, $route, $handler, $name);
        $this->setIsDirty();
    }

    /**
     * @inheritDoc
     */
    public function addGroup($prefix, callable $callback, ?string $name = null): void
    {
        $this->collector->group($prefix, $callback, $name, $this);
    }

    /**
     * @inheritDoc
     */
    public function map($httpMethod, $route, $handler, ?string $name = null, array $middlewares = []): void
    {
        $middlewares = array_merge($this->currentMiddlewares, $middlewares);
        $routeHandler = $this->createRoute($handler, $name, $middlewares);
        $this->collector->map($httpMethod, $route, $routeHandler, $name);
        $this->setIsDirty();
    }

    /**
     * @inheritDoc
     */
    public function group($prefix, callable $callback, ?string $name = null, array $middlewares = []): void
    {
        $previousMiddlewares = $this->currentMiddlewares;
        $this->currentMiddlewares = array_merge($previousMiddlewares, $middlewares);
        $this->collector->group($prefix, $callback, $name, $this);
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
     * @return Collector
     */
    public function getCollector(): Collector
    {
        return $this->collector;
    }

    /**
     * @return Invoker
     */
    public function getInvoker(): Invoker
    {
        if (!$this->invoker) {
            $this->invoker = new \Phact\Router\Invoker\Std($this->container);
        }
        return $this->invoker;
    }

    /**
     * Mark as dirty, data re-load needed
     */
    protected function setIsDirty(): void
    {
        $this->isDirty = true;
    }

    /**
     *
     * @throws InvalidArgumentException
     */
    protected function loadData(): void
    {
        $dispatcherData = [];
        $reverserData = [];
        $cacheRequired = true;

        // If data exists in cache, load it from cache
        if (!$this->isLoaded && $this->cache && $this->cache->has($this->cacheKey)) {
            // Extract data from cache
            [$dispatcherData, $reverserData] = $this->cache->get($this->cacheKey);
            $this->isLoaded = true;
            $cacheRequired = false;
        }

        // If not loaded
        if (!$this->isLoaded) {
            // If loader exists, load data with loader
            if ($this->loader) {
                $this->loader->load($this);
            }
            // Extract data from collector
            $dispatcherData = $this->getCollector()->getData();
            $reverserData = $this->getCollector()->getReverserData();
            $this->isLoaded = true;
        }

        // If no changes, just pass
        if (!$this->isDirty) {
            return;
        }

        // Update cache if needed
        if ($cacheRequired) {
            $this->cacheData($dispatcherData, $reverserData);
        }

        $this->createDispatcherAndReverser($dispatcherData, $reverserData);

        $this->isDirty = false;
    }

    /**
     * Add data to cache
     *
     * @param $dispatcherData
     * @param $reverserData
     * @throws InvalidArgumentException
     */
    protected function cacheData($dispatcherData, $reverserData): void
    {
        if ($this->cache) {
            $this->cache->set($this->cacheKey, [
                $dispatcherData,
                $reverserData
            ], $this->cacheTTL);
        }
    }

    /**
     * Create reverser and dispatcher with provided data
     *
     * @param $dispatcherData
     * @param $reverserData
     */
    protected function createDispatcherAndReverser($dispatcherData, $reverserData): void
    {
        $dispatcherClass = $this->dispatcherClass;
        $this->dispatcher = new $dispatcherClass($dispatcherData);

        $reverserClass = $this->reverserClass;
        $this->reverser = new $reverserClass($reverserData);
    }

    /**
     * @return Reverser
     */
    public function getReverser(): Reverser
    {
        $this->loadData();
        return $this->reverser;
    }

    /**
     * @return Dispatcher
     */
    public function getDispatcher(): Dispatcher
    {
        $this->loadData();
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
                $allowed = (array) $match[1];
                throw new MethodNotAllowedException($allowed);
                break;
            case Dispatcher::FOUND:
                $params = $match[2];
                $this->getInvoker()->invoke($request, $match[1], $params);
                break;
        }

        return $handler->handle($request);
    }
}