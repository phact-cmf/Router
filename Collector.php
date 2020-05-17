<?php declare(strict_types=1);

namespace Phact\Router;

use FastRoute\DataGenerator;
use FastRoute\RouteCollector as FastRouteCollector;
use FastRoute\RouteParser;

class Collector extends FastRouteCollector
{
    /**
     * @var ReverserDataGenerator
     */
    protected $reverserDataGenerator;

    /**
     * @var string
     */
    protected $currentGroupName = '';

    /**
     * Collector constructor.
     * @param RouteParser $routeParser
     * @param DataGenerator $dataGenerator
     * @param ReverserDataGenerator $reverserDataGenerator
     */
    public function __construct(
        RouteParser $routeParser,
        DataGenerator $dataGenerator,
        ReverserDataGenerator $reverserDataGenerator
    ) {
        parent::__construct($routeParser, $dataGenerator);
        $this->reverserDataGenerator = $reverserDataGenerator;
    }

    /**
     * @return string
     */
    public function getCurrentGroupName(): string
    {
        return $this->currentGroupName;
    }

    /**
     * Add route with name for reversing.
     *
     * @param string|string[] $httpMethod
     * @param string $route
     * @param mixed $handler
     * @param string $name
     */
    public function map($httpMethod, $route, $handler, ?string $name = null): void
    {
        $route = $this->currentGroupPrefix . $route;
        $routeDatas = $this->routeParser->parse($route);

        foreach ((array) $httpMethod as $method) {
            foreach ($routeDatas as $routeData) {
                $this->dataGenerator->addRoute($method, $routeData, $handler);
            }
        }
        if ($name !== null) {
            $name = $this->currentGroupName . $name;
            foreach ($routeDatas as $routeData) {
                $this->reverserDataGenerator->addRoute($name, $routeData);
            }
        }
    }

    /**
     * Create a route group with a common prefix and name
     *
     * All routes created in the passed callback will have the given group prefix and name prepended
     *
     * @param $prefix
     * @param callable $callback
     * @param string|null $name
     * @param null $callbackScope
     */
    public function group($prefix, callable $callback, ?string $name = null, $callbackScope = null): void
    {
        $previousGroupName = $this->currentGroupName;
        if ($name !== null) {
            $this->currentGroupName = $previousGroupName . $name;
        }
        $previousGroupPrefix = $this->currentGroupPrefix;
        $this->currentGroupPrefix = $previousGroupPrefix . $prefix;
        $callback($callbackScope ?: $this);
        $this->currentGroupPrefix = $previousGroupPrefix;
        $this->currentGroupName = $previousGroupName;
    }

    /**
     * @return array
     */
    public function getReverserData(): array
    {
        return $this->reverserDataGenerator->getData();
    }

}