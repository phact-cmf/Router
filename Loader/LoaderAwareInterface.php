<?php declare(strict_types=1);

namespace Phact\Router\Loader;

use Phact\Router\Loader;

interface LoaderAwareInterface
{
    public function setLoader(Loader $loader): void;
}