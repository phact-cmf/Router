<?php declare(strict_types=1);

namespace Phact\Router;

use FastRoute\Dispatcher;

interface DispatcherFactory
{
    public function createDispatcher($data): Dispatcher;
}
