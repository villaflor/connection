<?php

namespace Villaflor\Connection\Middleware;

use Psr\Http\Message\ResponseInterface;
use Villaflor\Connection\Cache\CacheInterface;

/**
 * Middleware for caching HTTP responses.
 *
 * Only caches GET requests by default. Cached responses are stored
 * based on the method, URI, and request data.
 */
class CachingMiddleware implements MiddlewareInterface
{
    /**
     * @param  CacheInterface  $cache  The cache implementation to use
     * @param  int  $defaultTtl  Default TTL in seconds (default: 300 = 5 minutes)
     * @param  array<string>  $cacheableMethods  Methods to cache (default: ['GET'])
     */
    public function __construct(
        private readonly CacheInterface $cache,
        private readonly int $defaultTtl = 300,
        private readonly array $cacheableMethods = ['GET']
    ) {}

    public function handle(
        string $method,
        string $uri,
        array $data,
        array $headers,
        callable $next
    ): ResponseInterface {
        // Only cache specified methods
        if (! in_array(strtoupper($method), array_map('strtoupper', $this->cacheableMethods))) {
            return $next($method, $uri, $data, $headers);
        }

        $cacheKey = $this->getCacheKey($method, $uri, $data);

        // Try to get from cache
        $cached = $this->cache->get($cacheKey);
        if ($cached instanceof ResponseInterface) {
            return $cached;
        }

        // Execute request
        $response = $next($method, $uri, $data, $headers);

        // Only cache successful responses (2xx status codes)
        $statusCode = $response->getStatusCode();
        if ($statusCode >= 200 && $statusCode < 300) {
            // Determine TTL from Cache-Control header or use default
            $ttl = $this->getTtlFromResponse($response) ?? $this->defaultTtl;

            // Store in cache
            $this->cache->set($cacheKey, $response, $ttl);
        }

        return $response;
    }

    /**
     * Generate a cache key from the request parameters.
     */
    private function getCacheKey(string $method, string $uri, array $data): string
    {
        return md5(strtoupper($method).'|'.$uri.'|'.json_encode($data));
    }

    /**
     * Extract TTL from Cache-Control or Expires headers.
     *
     * @return int|null TTL in seconds, or null if not specified
     */
    private function getTtlFromResponse(ResponseInterface $response): ?int
    {
        // Check Cache-Control header
        $cacheControl = $response->getHeaderLine('Cache-Control');
        if (preg_match('/max-age=(\d+)/', $cacheControl, $matches)) {
            return (int) $matches[1];
        }

        // Check Expires header
        $expires = $response->getHeaderLine('Expires');
        if ($expires) {
            $expiresTime = strtotime($expires);
            if ($expiresTime !== false) {
                $ttl = $expiresTime - time();

                return max(0, $ttl);
            }
        }

        return null;
    }
}
