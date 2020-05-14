<?php declare(strict_types=1);


namespace Phact\Router;


use Psr\Container\ContainerInterface;

trait ContainerAwareTrait
{
    protected $container;

    /**
     * @inheritDoc
     */
    public function setContainer(ContainerInterface $container): void
    {
        $this->container = $container;
    }
}