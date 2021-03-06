<?php declare(strict_types=1);

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
     * @var string|null
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
     * Get route original handler
     *
     * @return mixed
     */
    public function getOriginalHandler()
    {
        return $this->originalHandler;
    }

    /**
     * Get route Middlewares
     *
     * @return array
     */
    public function getMiddlewares(): array
    {
        return $this->middlewares;
    }

    /**
     * Get route name
     *
     * @return string|null
     */
    public function getName(): ?string
    {
        return $this->name;
    }
}
