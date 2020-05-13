<?php
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

    public function __construct(RouteParser $routeParser, DataGenerator $dataGenerator, ReverserDataGenerator $reverserDataGenerator)
    {
        parent::__construct($routeParser, $dataGenerator);
        $this->reverserDataGenerator = $reverserDataGenerator;
    }

    /**
     * Add route with name for reversing
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
        if ($name) {
            foreach ($routeDatas as $routeData) {
                $this->reverserDataGenerator->addRoute($name, $routeData);
            }
        }
    }

    /**
     * @return array
     */
    public function getReverserData(): array
    {
        return $this->reverserDataGenerator->getData();
    }
}