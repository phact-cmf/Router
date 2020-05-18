<?php declare(strict_types=1);

namespace Phact\Router;

interface ReverserFabric
{
    public function createReverser($data): Reverser;
}