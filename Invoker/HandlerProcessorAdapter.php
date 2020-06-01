<?php declare(strict_types=1);

namespace Phact\Router\Invoker;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class HandlerProcessorAdapter implements RequestHandlerInterface
{
    /**
     * @var HandlerProcessorInterface
     */
    protected $processor;

    protected $originalHandler;
    /**
     * @var array
     */
    protected $variables;

    public function __construct(HandlerProcessorInterface $processor, $originalHandler, array $variables = [])
    {
        $this->processor = $processor;
        $this->originalHandler = $originalHandler;
        $this->variables = $variables;
    }

    /**
     * @inheritDoc
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        return $this->processor->processHandler($request, $this->originalHandler, $this->variables);
    }
}
