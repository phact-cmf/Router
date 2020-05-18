<?php declare(strict_types=1);

namespace Tests\Mocks;

use Tests\Mocks\DummyResponse;
use Psr\Http\Message\ResponseInterface;

class DummyActionController
{
    public function action(): ResponseInterface
    {
        return new DummyResponse();
    }
}
