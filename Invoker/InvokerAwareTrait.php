<?php declare(strict_types=1);

namespace Phact\Router\Invoker;

use Phact\Router\Invoker;

trait InvokerAwareTrait
{
    /** @var Invoker */
    protected $invoker;

    /**
     * @inheritDoc
     */
    public function setInvoker(Invoker $invoker): void
    {
        $this->invoker = $invoker;
    }
}