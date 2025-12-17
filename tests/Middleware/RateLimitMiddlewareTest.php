<?php

use Villaflor\Connection\Http\Response;
use Villaflor\Connection\Http\Stream;
use Villaflor\Connection\Middleware\RateLimitMiddleware;
use Villaflor\Connection\RateLimit\RateLimiter;

it('throttles requests through middleware', function () {
    $limiter = new RateLimiter(maxRequests: 2, perSeconds: 1);
    $middleware = new RateLimitMiddleware($limiter);

    $next = function ($method, $uri, $data, $headers) {
        return new Response(200, [], new Stream('test'), 'OK');
    };

    $start = microtime(true);

    // First 2 requests should be fast
    $middleware->handle('GET', '/test', [], [], $next);
    $middleware->handle('GET', '/test', [], [], $next);

    // Third request should be throttled
    $response = $middleware->handle('GET', '/test', [], [], $next);

    $duration = microtime(true) - $start;

    expect($response->getStatusCode())->toBe(200);
    expect($duration)->toBeGreaterThan(0.3); // Should have waited
});

it('supports different keys for different rate limits', function () {
    $limiter = new RateLimiter(maxRequests: 1, perSeconds: 1);
    $middleware1 = new RateLimitMiddleware($limiter, 'key1');
    $middleware2 = new RateLimitMiddleware($limiter, 'key2');

    $next = function ($method, $uri, $data, $headers) {
        return new Response(200, [], new Stream('test'), 'OK');
    };

    // Both should work independently
    $response1 = $middleware1->handle('GET', '/test', [], [], $next);
    $response2 = $middleware2->handle('GET', '/test', [], [], $next);

    expect($response1->getStatusCode())->toBe(200);
    expect($response2->getStatusCode())->toBe(200);
});

it('integrates with real HTTP client', function () {
    $auth = $this->getMockBuilder(Villaflor\Connection\Auth\AuthInterface::class)
        ->onlyMethods(['getHeaders'])
        ->getMock();
    $auth->method('getHeaders')->willReturn([]);

    $client = new Villaflor\Connection\Adapter\Curl($auth, 'https://postman-echo.com');

    // Add rate limiting middleware - 2 requests per second
    $limiter = new RateLimiter(maxRequests: 2, perSeconds: 1);
    $middleware = new RateLimitMiddleware($limiter);
    $client->addMiddleware($middleware);

    $start = microtime(true);

    // Make 3 requests
    $client->get('/get');
    $client->get('/get');
    $client->get('/get'); // This one should be throttled

    $duration = microtime(true) - $start;

    // Should have taken at least 0.3 seconds due to rate limiting
    expect($duration)->toBeGreaterThan(0.3);
});
