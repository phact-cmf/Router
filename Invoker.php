<?php

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
     * @param array $variables Variables matched by router
     * @return ResponseInterface
     */
    public function invoke(ServerRequestInterface $request, $handler, array $variables): ResponseInterface;
}
