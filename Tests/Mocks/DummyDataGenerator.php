<?php declare(strict_types=1);

namespace Tests\Mocks;

use FastRoute\DataGenerator;

class DummyDataGenerator implements DataGenerator
{
    protected $routes = [];

    public function addRoute($httpMethod, $routeData, $handler)
    {
        $this->routes[] = [$httpMethod, $routeData, $handler];
    }

    public function getData()
    {
        return $this->routes;
    }
}
