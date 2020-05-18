<?php declare(strict_types=1);

namespace Phact\Router;

use FastRoute\Dispatcher;

interface DispatcherFabric
{
    public function createDispatcher($data): Dispatcher;
}