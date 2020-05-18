<?php declare(strict_types=1);

namespace Phact\Router\Reverser;

use Phact\Router\Reverser;
use Phact\Router\ReverserFactory;

class StdReverserFactory implements ReverserFactory
{
    public function createReverser($data): Reverser
    {
        return new Std($data);
    }
}