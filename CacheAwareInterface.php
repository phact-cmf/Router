<?php declare(strict_types=1);

namespace Phact\Router;

use DateInterval;

use Psr\SimpleCache\CacheInterface;

interface CacheAwareInterface
{
    /**
     * Setting cache implementation
     *
     * @param CacheInterface $cache
     */
    public function setCache(CacheInterface $cache): void;

    /**
     * Setting cache key name
     *
     * @param string $key
     */
    public function setCacheKey(string $key): void;

    /**
     * Setting cache TTL
     *
     * @param null|int|DateInterval $ttl
     */
    public function setCacheTTL($ttl = null): void;
}
