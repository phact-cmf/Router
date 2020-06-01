<?php declare(strict_types=1);

namespace Phact\Router\Invoker;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

interface HandlerProcessorInterface
{
    /**
     * Process route handler
     *
     * @param ServerRequestInterface $request
     * @param $handler
     * @param array $variables
     * @return ResponseInterface
     */
    public function processHandler(ServerRequestInterface $request, $handler, array $variables): ResponseInterface;
}
