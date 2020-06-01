<?php declare(strict_types=1);

namespace Phact\Router;

use Psr\Container\ContainerInterface;

trait ContainerAwareTrait
{
    /** @var ContainerInterface|null */
    protected $container;

    /**
     * @inheritDoc
     */
    public function setContainer(ContainerInterface $container): void
    {
        $this->container = $container;
    }
}
