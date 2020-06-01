<?php declare(strict_types=1);

namespace Phact\Router;

interface RouteCollector
{
    /**
     * Simple add route
     *
     * @param $httpMethod
     * @param $route
     * @param $handler
     * @param string|null $name
     */
    public function addRoute($httpMethod, $route, $handler, ?string $name = null): void;

    /**
     * Simple add group of routes
     *
     * @param $prefix
     * @param callable $callback
     * @param string|null $name
     */
    public function addGroup($prefix, callable $callback, ?string $name = null): void;

    /**
     * Add route with optional Middlewares
     *
     * @param $httpMethod
     * @param $route
     * @param $handler
     * @param string|null $name
     * @param array $middlewares
     */
    public function map($httpMethod, $route, $handler, ?string $name = null, array $middlewares = []): void;

    /**
     * Add group of routes with optional Middlewares
     *
     * @param $prefix
     * @param callable $callback
     * @param string|null $name
     * @param array $middlewares
     */
    public function group($prefix, callable $callback, ?string $name = null, array $middlewares = []): void;
}
