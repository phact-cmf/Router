<?php declare(strict_types=1);

namespace Phact\Router;

use FastRoute\BadRouteException;
use LogicException;

/**
 * Interface Reverser
 * @package Phact\Router
 */
interface Reverser
{
    /**
     * Reversed routing
     *
     * Generate the URL for a named route. Replace regexes with supplied parameters
     *
     * @param string $routeName
     * @param array $variables
     * @return string
     *
     * @throws BadRouteException
     * @throws LogicException
     */
    public function reverse(string $routeName, array $variables = []): string;
}