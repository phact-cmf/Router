<?php declare(strict_types=1);

namespace Phact\Router\Reverser;

use Phact\Router\Reverser;
use Phact\Router\ReverserFactory;

class StdReverserFactory implements ReverserFactory
{
    /**
     * Create Reverser object
     *
     * @param $data
     * @return Reverser
     */
    public function createReverser($data): Reverser
    {
        return new Std($data);
    }
}
