<?php declare(strict_types=1);

namespace Phact\Router;

use FastRoute\Dispatcher;

interface DispatcherFactory
{
    /**
     * Create Dispatcher with provided data
     *
     * @param $data
     * @return Dispatcher
     */
    public function createDispatcher($data): Dispatcher;
}
