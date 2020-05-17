<?php declare(strict_types=1);

namespace Tests\Mocks;

use FastRoute\DataGenerator;
use Phact\Router\ReverserDataGenerator;

class DummyReverserDataGenerator implements ReverserDataGenerator
{
    protected $routes = [];

    public function addRoute(string $routeName, $routeData): void
    {
        $this->routes[] = [$routeName, $routeData];
    }

    public function getData(): array
    {
        return $this->routes;
    }
}