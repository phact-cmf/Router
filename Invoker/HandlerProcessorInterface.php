<?php declare(strict_types=1);

namespace Phact\Router\Invoker;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

interface HandlerProcessorInterface
{
    public function processHandler(ServerRequestInterface $request, $handler, array $variables): ResponseInterface;
}
