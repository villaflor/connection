<?php

namespace Villaflor\Connection\Retry;

/**
 * Configuration for retry behavior.
 */
class RetryConfig
{
    /**
     * @param  int  $maxAttempts  Maximum number of retry attempts
     * @param  array<int>  $retryableStatusCodes  HTTP status codes that should trigger retry
     * @param  bool  $exponentialBackoff  Use exponential backoff for delays
     * @param  int  $baseDelay  Base delay in milliseconds
     * @param  int  $maxDelay  Maximum delay in milliseconds
     */
    public function __construct(
        private readonly int $maxAttempts = 3,
        private readonly array $retryableStatusCodes = [408, 429, 500, 502, 503, 504],
        private readonly bool $exponentialBackoff = true,
        private readonly int $baseDelay = 1000,
        private readonly int $maxDelay = 30000
    ) {}

    public function getMaxAttempts(): int
    {
        return $this->maxAttempts;
    }

    public function shouldRetry(int $statusCode): bool
    {
        return in_array($statusCode, $this->retryableStatusCodes);
    }

    public function getDelay(int $attempt): int
    {
        if (! $this->exponentialBackoff) {
            return $this->baseDelay;
        }

        // Exponential backoff: baseDelay * (2 ^ attempt)
        $delay = $this->baseDelay * (2 ** ($attempt - 1));

        return min($delay, $this->maxDelay);
    }
}
