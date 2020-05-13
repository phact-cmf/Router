<?php declare(strict_types=1);
/**
 *
 *
 * All rights reserved.
 *
 * @author Okulov Anton
 * @email qantus@mail.ru
 * @version 1.0
 * @date 12/05/2020 16:02
 */

namespace Phact\Router;

use Psr\Http\Server\MiddlewareInterface;

class Route implements RouterHandler
{
    /**
     * @var mixed
     */
    protected $originalHandler;
    /**
     * @var MiddlewareInterface[]
     */
    protected $middlewares;
    /**
     * @var string
     */
    private $name;

    /**
     * Handler constructor.
     *
     * @param mixed $originalHandler
     * @param array $middlewares
     * @param string|null $name
     */
    public function __construct($originalHandler, ?string $name = null, array $middlewares = [])
    {
        $this->originalHandler = $originalHandler;
        $this->middlewares = $middlewares;
        $this->name = $name;
    }

    /**
     * @return mixed
     */
    public function getOriginalHandler()
    {
        return $this->originalHandler;
    }

    /**
     * @return array
     */
    public function getMiddlewares(): array
    {
        return $this->middlewares;
    }

    public function getName(): string
    {
        return $this->name;
    }
}