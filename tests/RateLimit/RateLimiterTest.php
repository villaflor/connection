<?php

use Villaflor\Connection\RateLimit\RateLimiter;

it('allows requests within rate limit', function () {
    $limiter = new RateLimiter(maxRequests: 10, perSeconds: 1);

    // Should allow first 10 requests immediately
    for ($i = 0; $i < 10; $i++) {
        expect($limiter->attempt())->toBeTrue();
    }
});

it('throttles requests exceeding rate limit', function () {
    $limiter = new RateLimiter(maxRequests: 2, perSeconds: 1);

    $start = microtime(true);

    // First 2 requests should be immediate
    expect($limiter->attempt())->toBeTrue();
    expect($limiter->attempt())->toBeTrue();

    // Third request should wait
    expect($limiter->attempt())->toBeTrue();

    $duration = microtime(true) - $start;

    // Should have waited at least 0.4 seconds (allowing some margin for execution time)
    expect($duration)->toBeGreaterThan(0.3);
});

it('can check if request would be allowed', function () {
    $limiter = new RateLimiter(maxRequests: 2, perSeconds: 1);

    expect($limiter->check())->toBeTrue();

    $limiter->attempt();
    expect($limiter->check())->toBeTrue();

    $limiter->attempt();
    expect($limiter->check())->toBeFalse(); // No tokens left
});

it('supports multiple keys for different rate limits', function () {
    $limiter = new RateLimiter(maxRequests: 2, perSeconds: 1);

    // Use different keys for different endpoints
    expect($limiter->attempt('endpoint1'))->toBeTrue();
    expect($limiter->attempt('endpoint1'))->toBeTrue();

    // endpoint1 is now at limit, but endpoint2 should still work
    expect($limiter->attempt('endpoint2'))->toBeTrue();
    expect($limiter->attempt('endpoint2'))->toBeTrue();
});

it('can reset rate limiter for a specific key', function () {
    $limiter = new RateLimiter(maxRequests: 1, perSeconds: 1);

    $limiter->attempt();
    expect($limiter->check())->toBeFalse(); // At limit

    $limiter->reset();
    expect($limiter->check())->toBeTrue(); // Reset
});

it('refills tokens over time', function () {
    $limiter = new RateLimiter(maxRequests: 2, perSeconds: 1);

    // Consume all tokens
    $limiter->attempt();
    $limiter->attempt();

    expect($limiter->check())->toBeFalse();

    // Wait for tokens to refill
    usleep(600000); // 0.6 seconds

    // Should have at least 1 token available now
    expect($limiter->check())->toBeTrue();
});

it('handles high rate limits efficiently', function () {
    $limiter = new RateLimiter(maxRequests: 100, perSeconds: 1);

    $start = microtime(true);

    // Should handle 100 requests quickly
    for ($i = 0; $i < 100; $i++) {
        expect($limiter->attempt())->toBeTrue();
    }

    $duration = microtime(true) - $start;

    // Should complete in less than 0.1 seconds
    expect($duration)->toBeLessThan(0.1);
});
