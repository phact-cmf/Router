<?php declare(strict_types=1);

namespace Phact\Router;

interface ReverserFactory
{
    public function createReverser($data): Reverser;
}
