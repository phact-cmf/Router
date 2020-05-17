<?php declare(strict_types=1);

namespace Tests\Mocks;

use FastRoute\RouteParser;

class DummyParser implements RouteParser
{
    public function parse($route)
    {
        return [[$route]];
    }
}