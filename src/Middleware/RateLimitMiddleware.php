<?php

namespace Villaflor\Connection\Middleware;

use Psr\Http\Message\ResponseInterface;
use Villaflor\Connection\RateLimit\RateLimiter;

/**
 * Middleware for rate limiting HTTP requests.
 *
 * This middleware automatically throttles requests to stay within configured
 * rate limits, preventing API quota exhaustion and rate limit errors.
 */
class RateLimitMiddleware implements MiddlewareInterface
{
    /**
     * Create a new rate limit middleware instance.
     *
     * @param  RateLimiter  $limiter  The rate limiter instance
     * @param  string  $key  Rate limit key (e.g., 'api', 'default')
     */
    public function __construct(
        private readonly RateLimiter $limiter,
        private readonly string $key = 'default'
    ) {}

    public function handle(
        string $method,
        string $uri,
        array $data,
        array $headers,
        callable $next
    ): ResponseInterface {
        // Wait for rate limit token to become available
        $this->limiter->attempt($this->key);

        // Proceed with the request
        return $next($method, $uri, $data, $headers);
    }
}
