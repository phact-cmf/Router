<?php declare(strict_types=1);
/**
 *
 *
 * All rights reserved.
 *
 * @author Okulov Anton
 * @email qantus@mail.ru
 * @version 1.0
 * @date 12/05/2020 16:04
 */

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