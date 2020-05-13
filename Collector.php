<?php declare(strict_types=1);
/**
 *
 *
 * All rights reserved.
 *
 * @author Okulov Anton
 * @email qantus@mail.ru
 * @version 1.0
 * @date 12/05/2020 11:30
 */

namespace Phact\Router;

use FastRoute\DataGenerator;
use FastRoute\RouteCollector;
use FastRoute\RouteParser;

class Collector extends RouteCollector
{
    /**
     * @var ReverserDataGenerator
     */
    protected $reverserDataGenerator;

    /**
     * @var string
     */
    protected $currentGroupName;

    public function __construct(RouteParser $routeParser, DataGenerator $dataGenerator, ReverserDataGenerator $reverserDataGenerator)
    {
        parent::__construct($routeParser, $dataGenerator);
        $this->reverserDataGenerator = $reverserDataGenerator;
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
     */
    public function group($prefix, callable $callback, ?string $name = null): void
    {
        $previousGroupName = $this->currentGroupName;
        if ($name !== null) {
            $this->currentGroupName = $previousGroupName . $name;
        }
        $this->addGroup($prefix, $callback);
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