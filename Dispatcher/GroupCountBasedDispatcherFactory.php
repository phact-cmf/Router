<?php declare(strict_types=1);

namespace Phact\Router\Dispatcher;

use FastRoute\Dispatcher;
use Phact\Router\DispatcherFactory;

class GroupCountBasedDispatcherFactory implements DispatcherFactory
{
    public function createDispatcher($data): Dispatcher
    {
        return new Dispatcher\GroupCountBased($data);
    }
}
