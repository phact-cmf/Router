<?php declare(strict_types=1);

namespace Phact\Router\Exception;

use Throwable;

/**
 * Default "Not Found" Exception
 *
 * Class NotFoundException
 * @package Phact\Router\Exception
 */
class NotFoundException extends HttpException
{
    public function __construct(array $allowed = [], $message = 'Not Found', $code = 0, Throwable $previous = null)
    {
        parent::__construct(404, $message, $code, $previous);
    }
}
