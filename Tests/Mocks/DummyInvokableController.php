<?php declare(strict_types=1);

namespace Tests\Mocks;

use Tests\Mocks\DummyResponse;

class DummyInvokableController
{
    public function __invoke()
    {
        return new DummyResponse();
    }
}
