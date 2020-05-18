<?php declare(strict_types=1);

namespace Tests\Mocks;

use Mocks\EmptyResponse;

class InvokableController
{
    public function __invoke()
    {
        return new EmptyResponse();
    }
}