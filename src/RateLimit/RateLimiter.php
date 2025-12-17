<?php

namespace Villaflor\Connection\RateLimit;

/**
 * Rate limiter to control request frequency.
 *
 * This class implements a token bucket algorithm to limit the rate of requests.
 */
class RateLimiter
{
    private array $buckets = [];

    /**
     * Create a new rate limiter instance.
     *
     * @param  int  $maxRequests  Maximum number of requests allowed
     * @param  int  $perSeconds  Time window in seconds
     */
    public function __construct(
        private readonly int $maxRequests,
        private readonly int $perSeconds
    ) {}

    /**
     * Attempt to acquire a token, waiting if necessary.
     *
     * @param  string  $key  Identifier for the rate limit bucket (e.g., domain, endpoint)
     * @return bool True if token was acquired
     */
    public function attempt(string $key = 'default'): bool
    {
        $now = microtime(true);

        if (! isset($this->buckets[$key])) {
            $this->buckets[$key] = [
                'tokens' => $this->maxRequests,
                'last_refill' => $now,
            ];
        }

        $bucket = &$this->buckets[$key];

        // Refill tokens based on time elapsed
        $timePassed = $now - $bucket['last_refill'];
        $tokensToAdd = ($timePassed / $this->perSeconds) * $this->maxRequests;
        $bucket['tokens'] = min($this->maxRequests, $bucket['tokens'] + $tokensToAdd);
        $bucket['last_refill'] = $now;

        // Check if we have tokens available
        if ($bucket['tokens'] >= 1) {
            $bucket['tokens'] -= 1;

            return true;
        }

        // Calculate wait time until next token is available
        $tokensNeeded = 1 - $bucket['tokens'];
        $waitTime = ($tokensNeeded / $this->maxRequests) * $this->perSeconds;

        // Wait for the token to become available
        usleep((int) ($waitTime * 1000000));

        $bucket['tokens'] = 0;
        $bucket['last_refill'] = microtime(true);

        return true;
    }

    /**
     * Check if a request would be allowed without consuming a token.
     *
     * @param  string  $key  Identifier for the rate limit bucket
     * @return bool True if request would be allowed
     */
    public function check(string $key = 'default'): bool
    {
        if (! isset($this->buckets[$key])) {
            return true;
        }

        $bucket = $this->buckets[$key];
        $now = microtime(true);

        // Calculate current tokens
        $timePassed = $now - $bucket['last_refill'];
        $tokensToAdd = ($timePassed / $this->perSeconds) * $this->maxRequests;
        $currentTokens = min($this->maxRequests, $bucket['tokens'] + $tokensToAdd);

        return $currentTokens >= 1;
    }

    /**
     * Reset the rate limiter for a specific key.
     *
     * @param  string  $key  Identifier for the rate limit bucket
     */
    public function reset(string $key = 'default'): void
    {
        unset($this->buckets[$key]);
    }
}
