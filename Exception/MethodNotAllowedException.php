<?php declare(strict_types=1);

namespace Phact\Router\Exception;

use Throwable;

/**
 * Will thrown if method not allowed for requested route
 *
 * Class MethodNotAllowedException
 * @package Phact\Router\Exception
 */
class MethodNotAllowedException extends HttpException
{
    public function __construct(
        array $allowed = [],
        $message = 'Method Not Allowed',
        $code = 0,
        Throwable $previous = null
    ) {
        parent::__construct(405, $message, $code, $previous);
    }
}
