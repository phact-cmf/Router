<?php declare(strict_types=1);

namespace Phact\Router;

use Phact\Router\Exception\NotFoundException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Default handler for usage with process() method of Router
 *
 * Class NotFoundHandler
 * @package Phact\Router
 */
class NotFoundHandler implements RequestHandlerInterface
{
    /**
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     * @throws NotFoundException
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        throw new NotFoundException();
    }
}
