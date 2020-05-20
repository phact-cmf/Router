<?php declare(strict_types=1);

namespace Phact\Router;

use Psr\Container\ContainerInterface;

interface ContainerAwareInterface
{
    /**
     * @param ContainerInterface $container
     * @return mixed
     */
    public function setContainer(ContainerInterface $container): void;
}
