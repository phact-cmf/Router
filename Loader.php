<?php declare(strict_types=1);

namespace Phact\Router;

interface Loader
{
    /**
     * Load all routes to collector
     *
     * @param RouteCollector $collector
     * @return mixed
     */
    public function load(RouteCollector $collector);
}