<?php declare(strict_types=1);

namespace Phact\Router\Reverser;

use Phact\Router\Reverser;
use Phact\Router\ReverserFabric;

class StdReverserFabric implements ReverserFabric
{
    public function createReverser($data): Reverser
    {
        return new Std($data);
    }
}