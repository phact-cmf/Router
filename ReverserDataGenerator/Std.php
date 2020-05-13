<?php declare(strict_types=1);
/**
 *
 *
 * All rights reserved.
 *
 * @author Okulov Anton
 * @email qantus@mail.ru
 * @version 1.0
 * @date 12/05/2020 13:24
 */

namespace Phact\Router\ReverserDataGenerator;

use FastRoute\BadRouteException;
use Phact\Router\ReverserDataGenerator;

class Std implements ReverserDataGenerator
{
    protected $routes = [];

    /**
     * @inheritDoc
     */
    public function addRoute(string $routeName, $routeData): void
    {
        if (isset($this->routes[$routeName])) {
            throw new BadRouteException(sprintf(
                'Cannot register two routes with same name "%s"',
                $routeName
            ));
        }
        $this->routes[$routeName] = $routeData;
    }

    /**
     * @inheritDoc
     */
    public function getData(): array
    {
        return $this->routes;
    }
}