<?php declare(strict_types=1);

namespace Phact\Router;

interface ReverserFactory
{
    /**
     * Create Reverser with provided data
     *
     * @param $data
     * @return Reverser
     */
    public function createReverser($data): Reverser;
}
