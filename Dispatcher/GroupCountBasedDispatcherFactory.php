<?php declare(strict_types=1);

namespace Phact\Router\Dispatcher;

use FastRoute\Dispatcher;
use Phact\Router\DispatcherFactory;

/**
 * Default dispatcher factory that provided GroupCountBased strategy
 *
 * Class GroupCountBasedDispatcherFactory
 * @package Phact\Router\Dispatcher
 */
class GroupCountBasedDispatcherFactory implements DispatcherFactory
{
    /**
     * @inheritDoc
     */
    public function createDispatcher($data): Dispatcher
    {
        return new Dispatcher\GroupCountBased($data);
    }
}
