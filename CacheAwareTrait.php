<?php declare(strict_types=1);

namespace Phact\Router;

use DateInterval;
use Psr\SimpleCache\CacheInterface;

trait CacheAwareTrait
{
    /** @var CacheInterface */
    protected $cache;

    /** @var string */
    protected $cacheKey;

    /** @var null|int|DateInterval */
    protected $cacheTTL;

    /**
     * @inheritDoc
     */
    public function setCache(CacheInterface $cache): void
    {
        $this->cache = $cache;
    }

    /**
     * @inheritDoc
     */
    public function setCacheKey(string $key = 'routes'): void
    {
        $this->cacheKey = $key;
    }

    /**
     * @inheritDoc
     */
    public function setCacheTTL($ttl = 60): void
    {
        $this->cacheTTL = $ttl;
    }
}