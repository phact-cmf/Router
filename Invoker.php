<?php
/**
 *
 *
 * All rights reserved.
 *
 * @author Okulov Anton
 * @email qantus@mail.ru
 * @version 1.0
 * @date 13/05/2020 08:34
 */

namespace Phact\Router;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

interface Invoker
{
    /**
     * Handler with ServerRequestInterface
     *
     * @param ServerRequestInterface $request
     * @param mixed $handler Original handler from route
     * @param array $middlewares Array of unresolved middlewares
     * @param array $variables Variables matched by router
     * @return ResponseInterface
     */
    public function invoke(ServerRequestInterface $request, $handler, array $middlewares, array $variables): ResponseInterface;
}