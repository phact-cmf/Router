<?php declare(strict_types=1);

namespace Phact\Router\Exception;

use Throwable;

class NotFoundException extends HttpException
{
    public function __construct(array $allowed = [], $message = 'Not Found', $code = 0, Throwable $previous = null)
    {
        parent::__construct(404, $message, $code, $previous);
    }
}
