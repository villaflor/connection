<?php

use Villaflor\Connection\Retry\RetryConfig;

it('can create retry config with default values', function () {
    $config = new RetryConfig;

    expect($config->getMaxAttempts())->toBe(3);
    expect($config->shouldRetry(503))->toBeTrue();
    expect($config->shouldRetry(200))->toBeFalse();
});

it('can create retry config with custom values', function () {
    $config = new RetryConfig(
        maxAttempts: 5,
        retryableStatusCodes: [500, 502],
        exponentialBackoff: false,
        baseDelay: 2000,
        maxDelay: 60000
    );

    expect($config->getMaxAttempts())->toBe(5);
    expect($config->shouldRetry(500))->toBeTrue();
    expect($config->shouldRetry(503))->toBeFalse();
});

it('returns correct delay with exponential backoff', function () {
    $config = new RetryConfig(
        baseDelay: 1000,
        exponentialBackoff: true
    );

    expect($config->getDelay(1))->toBe(1000);  // 1000 * (2^0)
    expect($config->getDelay(2))->toBe(2000);  // 1000 * (2^1)
    expect($config->getDelay(3))->toBe(4000);  // 1000 * (2^2)
    expect($config->getDelay(4))->toBe(8000);  // 1000 * (2^3)
});

it('returns correct delay without exponential backoff', function () {
    $config = new RetryConfig(
        baseDelay: 1000,
        exponentialBackoff: false
    );

    expect($config->getDelay(1))->toBe(1000);
    expect($config->getDelay(2))->toBe(1000);
    expect($config->getDelay(3))->toBe(1000);
});

it('respects max delay with exponential backoff', function () {
    $config = new RetryConfig(
        baseDelay: 1000,
        maxDelay: 5000,
        exponentialBackoff: true
    );

    expect($config->getDelay(1))->toBe(1000);
    expect($config->getDelay(2))->toBe(2000);
    expect($config->getDelay(3))->toBe(4000);
    expect($config->getDelay(4))->toBe(5000);  // Capped at maxDelay
    expect($config->getDelay(5))->toBe(5000);  // Still capped
});

it('correctly identifies retryable status codes', function () {
    $config = new RetryConfig;

    // Default retryable codes: [408, 429, 500, 502, 503, 504]
    expect($config->shouldRetry(408))->toBeTrue();
    expect($config->shouldRetry(429))->toBeTrue();
    expect($config->shouldRetry(500))->toBeTrue();
    expect($config->shouldRetry(502))->toBeTrue();
    expect($config->shouldRetry(503))->toBeTrue();
    expect($config->shouldRetry(504))->toBeTrue();
});

it('correctly identifies non-retryable status codes', function () {
    $config = new RetryConfig;

    expect($config->shouldRetry(200))->toBeFalse();
    expect($config->shouldRetry(400))->toBeFalse();
    expect($config->shouldRetry(401))->toBeFalse();
    expect($config->shouldRetry(403))->toBeFalse();
    expect($config->shouldRetry(404))->toBeFalse();
});
