<?php

namespace App\Service\RateLimit;

use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

class RateLimiter
{
    private int $maxRequests;
    private int $windowSeconds;
    private CacheInterface $cache;

    public function __construct(
        CacheInterface $cache,
        int $maxRequests = 5,
        int $windowSeconds = 60,
    ) {
        $this->cache = $cache;
        $this->maxRequests = $maxRequests;
        $this->windowSeconds = $windowSeconds;
    }

    /**
     * @throws \RuntimeException|\Psr\Cache\InvalidArgumentException when rate limit is exceeded
     */
    public function checkOrThrow(string $clientId): void
    {
        $cacheKey = 'rate_limit_'.md5($clientId);

        $count = $this->cache->get($cacheKey, function (ItemInterface $item) {
            $item->expiresAfter($this->windowSeconds);

            return 0;
        });

        if ($count >= $this->maxRequests) {
            throw new \RuntimeException('Rate limit exceeded. Try again later.');
        }

        // Increment
        $this->cache->delete($cacheKey);
        $this->cache->get($cacheKey, function (ItemInterface $item) use ($count) {
            $item->expiresAfter($this->windowSeconds);

            return $count + 1;
        });
    }
}
