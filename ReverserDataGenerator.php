<?php

namespace Phact\Router;

/**
 * Interface ReverserDataGenerator
 * @package Phact\Router
 */
interface ReverserDataGenerator
{
    /**
     * Adds a route to the reverser. The route data uses the
     * same format that is returned by RouterParser::parser().
     *
     * @param string $routeName
     * @param $routeData
     */
    public function addRoute(string $routeName, $routeData): void;

    /**
     * Return prepared routes data for reversing
     *
     * @return array
     */
    public function getData(): array;
}