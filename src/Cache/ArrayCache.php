<?php

namespace Villaflor\Connection\Cache;

/**
 * Simple in-memory array-based cache implementation.
 *
 * This cache stores items in memory for the duration of the request.
 * It's useful for testing and avoiding duplicate requests within a single execution.
 */
class ArrayCache implements CacheInterface
{
    /**
     * @var array<string, array{value: mixed, expires_at: int}>
     */
    private array $storage = [];

    public function get(string $key): mixed
    {
        if (! $this->has($key)) {
            return null;
        }

        return $this->storage[$key]['value'];
    }

    public function set(string $key, mixed $value, int $ttl): void
    {
        $this->storage[$key] = [
            'value' => $value,
            'expires_at' => time() + $ttl,
        ];
    }

    public function has(string $key): bool
    {
        if (! isset($this->storage[$key])) {
            return false;
        }

        // Check if expired
        if (time() >= $this->storage[$key]['expires_at']) {
            unset($this->storage[$key]);

            return false;
        }

        return true;
    }

    public function delete(string $key): void
    {
        unset($this->storage[$key]);
    }

    public function clear(): void
    {
        $this->storage = [];
    }

    /**
     * Get the number of items currently in cache.
     */
    public function count(): int
    {
        // Clean expired items first
        foreach (array_keys($this->storage) as $key) {
            $this->has($key);
        }

        return count($this->storage);
    }
}
