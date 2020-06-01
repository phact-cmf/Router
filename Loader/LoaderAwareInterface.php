<?php declare(strict_types=1);

namespace Phact\Router\Loader;

use Phact\Router\Loader;

interface LoaderAwareInterface
{
    /**
     * Set Loader
     *
     * @param Loader $loader
     */
    public function setLoader(Loader $loader): void;
}
