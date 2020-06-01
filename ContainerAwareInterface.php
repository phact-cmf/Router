<?php declare(strict_types=1);

namespace Phact\Router;

use Psr\Container\ContainerInterface;


interface ContainerAwareInterface
{
    /**
     * Setting PSR container implementation
     *
     * @param ContainerInterface $container
     * @return mixed
     */
    public function setContainer(ContainerInterface $container): void;
}
