<?php declare(strict_types=1);

namespace Phact\Router;

interface RouteCollector
{
    /**
     * @param $httpMethod
     * @param $route
     * @param $handler
     * @param string|null $name
     */
    public function addRoute($httpMethod, $route, $handler, ?string $name = null): void;

    /**
     * @param $prefix
     * @param callable $callback
     * @param string|null $name
     */
    public function addGroup($prefix, callable $callback, ?string $name = null): void;

    /**
     * @param $httpMethod
     * @param $route
     * @param $handler
     * @param string|null $name
     * @param array $middlewares
     */
    public function map($httpMethod, $route, $handler, ?string $name = null, array $middlewares = []): void;

    /**
     * @param $prefix
     * @param callable $callback
     * @param string|null $name
     * @param array $middlewares
     */
    public function group($prefix, callable $callback, ?string $name = null, array $middlewares = []): void;
}
