<?php declare(strict_types=1);

namespace Phact\Router;

use DateInterval;

use Psr\SimpleCache\CacheInterface;

interface CacheAwareInterface
{
    /**
     * @param CacheInterface $cache
     */
    public function setCache(CacheInterface $cache): void;

    /**
     * @param string $key
     */
    public function setCacheKey(string $key): void;

    /**
     * @param null|int|DateInterval $ttl
     */
    public function setCacheTTL($ttl = null): void;
}
