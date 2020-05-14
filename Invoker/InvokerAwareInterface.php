<?php declare(strict_types=1);

namespace Phact\Router\Invoker;

use Phact\Router\Invoker;

interface InvokerAwareInterface
{
    /**
     * @param Invoker $invoker
     * @return mixed
     */
    public function setInvoker(Invoker $invoker): void;
}