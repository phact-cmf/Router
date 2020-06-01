<?php declare(strict_types=1);

namespace Phact\Router\Exception;

use Throwable;

/**
 * Default HTTP exception
 *
 * Class HttpException
 * @package Phact\Router\Exception
 */
abstract class HttpException extends \Exception
{
    /**
     * @var int
     */
    private $status;

    public function __construct(int $status, $message = "", $code = 0, Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
        $this->status = $status;
    }

    /**
     * Get HTTP status
     *
     * @return int
     */
    public function getStatusCode(): int
    {
        return $this->status;
    }
}
