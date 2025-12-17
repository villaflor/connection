<?php

namespace Villaflor\Connection\Cache;

/**
 * Simple cache interface for HTTP response caching.
 *
 * This is a minimal cache interface focused on the needs of HTTP caching.
 * For more advanced caching needs, consider using PSR-16 SimpleCache.
 */
interface CacheInterface
{
    /**
     * Retrieve an item from the cache.
     *
     * @param  string  $key  The cache key
     * @return mixed The cached value, or null if not found or expired
     */
    public function get(string $key): mixed;

    /**
     * Store an item in the cache.
     *
     * @param  string  $key  The cache key
     * @param  mixed  $value  The value to cache
     * @param  int  $ttl  Time to live in seconds
     */
    public function set(string $key, mixed $value, int $ttl): void;

    /**
     * Check if an item exists in the cache and is not expired.
     *
     * @param  string  $key  The cache key
     * @return bool True if the item exists and is valid
     */
    public function has(string $key): bool;

    /**
     * Remove an item from the cache.
     *
     * @param  string  $key  The cache key
     */
    public function delete(string $key): void;

    /**
     * Clear all items from the cache.
     */
    public function clear(): void;
}
