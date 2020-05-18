<?php declare(strict_types=1);

namespace Phact\Router\Dispatcher;

use FastRoute\Dispatcher;
use Phact\Router\DispatcherFabric;

class GroupCountBasedDispatcherFabric implements DispatcherFabric
{
    public function createDispatcher($data): Dispatcher
    {
        return new Dispatcher\GroupCountBased($data);
    }
}
