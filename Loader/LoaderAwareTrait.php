<?php declare(strict_types=1);

namespace Phact\Router\Loader;

use Phact\Router\Loader;

trait LoaderAwareTrait
{
    /**
     * @var Loader
     */
    protected $loader;

    /**
     * @inheritDoc
     */
    public function setLoader(Loader $loader): void
    {
        $this->loader = $loader;
    }
}
