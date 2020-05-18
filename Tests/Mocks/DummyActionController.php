<?php declare(strict_types=1);

namespace Tests\Mocks;

use Mocks\EmptyResponse;
use Psr\Http\Message\ResponseInterface;

class ActionController
{
    public function action(): ResponseInterface
    {
        return new EmptyResponse();
    }
}