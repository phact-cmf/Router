<?php declare(strict_types=1);

namespace Phact\Router;

use Psr\Http\Server\MiddlewareInterface;

interface RouterHandler
{
    /**
     * Return all middleware for route
     *
     * @return MiddlewareInterface[]
     */
    public function getMiddlewares(): array;

    /**
     * Route name
     *
     * @return string
     */
    public function getName(): string;

    /**
     * Return original handler
     *
     * @return mixed
     */
    public function getOriginalHandler();
}