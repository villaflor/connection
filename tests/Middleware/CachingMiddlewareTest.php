<?php

use Villaflor\Connection\Cache\ArrayCache;
use Villaflor\Connection\Http\Response;
use Villaflor\Connection\Http\Stream;
use Villaflor\Connection\Middleware\CachingMiddleware;

test('caches GET requests', function () {
    $cache = new ArrayCache;
    $middleware = new CachingMiddleware($cache, defaultTtl: 60);

    $callCount = 0;
    $next = function ($method, $uri, $data, $headers) use (&$callCount) {
        $callCount++;

        return new Response(200, [], new Stream(json_encode(['data' => 'test'])), 'OK');
    };

    // First call - cache miss
    $response1 = $middleware->handle('GET', '/test', [], [], $next);
    expect($callCount)->toBe(1);
    expect($response1->getStatusCode())->toBe(200);

    // Second call - cache hit (call count should not increase)
    $response2 = $middleware->handle('GET', '/test', [], [], $next);
    expect($callCount)->toBe(1); // Should not increment - served from cache
    expect($response2->getStatusCode())->toBe(200);

    // Verify it's the same cached response object
    expect($response2)->toBe($response1);
});

test('does not cache POST requests by default', function () {
    $cache = new ArrayCache;
    $middleware = new CachingMiddleware($cache, defaultTtl: 60);

    $callCount = 0;
    $next = function ($method, $uri, $data, $headers) use (&$callCount) {
        $callCount++;

        return new Response(200, [], new Stream(json_encode(['data' => 'test'])), 'OK');
    };

    // First POST
    $middleware->handle('POST', '/test', [], [], $next);
    expect($callCount)->toBe(1);

    // Second POST - should not be cached
    $middleware->handle('POST', '/test', [], [], $next);
    expect($callCount)->toBe(2);
});

test('can configure cacheable methods', function () {
    $cache = new ArrayCache;
    $middleware = new CachingMiddleware($cache, defaultTtl: 60, cacheableMethods: ['GET', 'POST']);

    $callCount = 0;
    $next = function ($method, $uri, $data, $headers) use (&$callCount) {
        $callCount++;

        return new Response(200, [], new Stream(json_encode(['data' => 'test'])), 'OK');
    };

    // First POST
    $middleware->handle('POST', '/test', [], [], $next);
    expect($callCount)->toBe(1);

    // Second POST - should be cached now
    $middleware->handle('POST', '/test', [], [], $next);
    expect($callCount)->toBe(1);
});

test('different URIs create different cache entries', function () {
    $cache = new ArrayCache;
    $middleware = new CachingMiddleware($cache, defaultTtl: 60);

    $callCount = 0;
    $next = function ($method, $uri, $data, $headers) use (&$callCount) {
        $callCount++;

        return new Response(200, [], new Stream(json_encode(['uri' => $uri])), 'OK');
    };

    $response1 = $middleware->handle('GET', '/test1', [], [], $next);
    $response2 = $middleware->handle('GET', '/test2', [], [], $next);

    expect($callCount)->toBe(2);
    expect($response1->getBody()->getContents())->toContain('/test1');
    expect($response2->getBody()->getContents())->toContain('/test2');
});

test('different request data creates different cache entries', function () {
    $cache = new ArrayCache;
    $middleware = new CachingMiddleware($cache, defaultTtl: 60);

    $callCount = 0;
    $next = function ($method, $uri, $data, $headers) use (&$callCount) {
        $callCount++;

        return new Response(200, [], new Stream(json_encode(['data' => $data])), 'OK');
    };

    $middleware->handle('GET', '/test', ['param' => '1'], [], $next);
    $middleware->handle('GET', '/test', ['param' => '2'], [], $next);

    expect($callCount)->toBe(2);
});

test('does not cache error responses', function () {
    $cache = new ArrayCache;
    $middleware = new CachingMiddleware($cache, defaultTtl: 60);

    $callCount = 0;
    $next = function ($method, $uri, $data, $headers) use (&$callCount) {
        $callCount++;

        return new Response(404, [], new Stream(json_encode(['error' => 'Not Found'])), 'Not Found');
    };

    // First call
    $middleware->handle('GET', '/test', [], [], $next);
    expect($callCount)->toBe(1);

    // Second call - should not be cached
    $middleware->handle('GET', '/test', [], [], $next);
    expect($callCount)->toBe(2);
});

test('respects cache-control max-age header', function () {
    $cache = new ArrayCache;
    $middleware = new CachingMiddleware($cache, defaultTtl: 300);

    $next = function ($method, $uri, $data, $headers) {
        return new Response(200, ['Cache-Control' => 'max-age=1'], new Stream('test'), 'OK');
    };

    // First call
    $middleware->handle('GET', '/test', [], [], $next);
    expect($cache->has(md5('GET|/test|[]')))->toBeTrue();

    // Wait for cache to expire (based on max-age=1)
    sleep(2);

    expect($cache->has(md5('GET|/test|[]')))->toBeFalse();
});

test('respects expires header', function () {
    $cache = new ArrayCache;
    $middleware = new CachingMiddleware($cache, defaultTtl: 300);

    $expiresAt = gmdate('D, d M Y H:i:s', time() + 1).' GMT';
    $next = function ($method, $uri, $data, $headers) use ($expiresAt) {
        return new Response(200, ['Expires' => $expiresAt], new Stream('test'), 'OK');
    };

    // First call
    $middleware->handle('GET', '/test', [], [], $next);
    expect($cache->has(md5('GET|/test|[]')))->toBeTrue();

    // Wait for cache to expire
    sleep(2);

    expect($cache->has(md5('GET|/test|[]')))->toBeFalse();
});

test('prefers cache-control over expires', function () {
    $cache = new ArrayCache;
    $middleware = new CachingMiddleware($cache, defaultTtl: 300);

    $expiresAt = gmdate('D, d M Y H:i:s', time() + 100).' GMT';
    $next = function ($method, $uri, $data, $headers) use ($expiresAt) {
        return new Response(200, [
            'Cache-Control' => 'max-age=1',
            'Expires' => $expiresAt,
        ], new Stream('test'), 'OK');
    };

    // First call
    $middleware->handle('GET', '/test', [], [], $next);
    expect($cache->has(md5('GET|/test|[]')))->toBeTrue();

    // Cache should expire based on max-age (1 second), not Expires (100 seconds)
    sleep(2);

    expect($cache->has(md5('GET|/test|[]')))->toBeFalse();
});

test('uses default ttl when no cache headers present', function () {
    $cache = new ArrayCache;
    $middleware = new CachingMiddleware($cache, defaultTtl: 1);

    $next = function ($method, $uri, $data, $headers) {
        return new Response(200, [], new Stream('test'), 'OK');
    };

    // First call
    $middleware->handle('GET', '/test', [], [], $next);
    expect($cache->has(md5('GET|/test|[]')))->toBeTrue();

    // Wait for default TTL to expire
    sleep(2);

    expect($cache->has(md5('GET|/test|[]')))->toBeFalse();
});

test('caches only successful 2xx responses', function () {
    $cache = new ArrayCache;
    $middleware = new CachingMiddleware($cache, defaultTtl: 60);

    $callCount = 0;
    $statusCode = 200;

    $next = function ($method, $uri, $data, $headers) use (&$callCount, &$statusCode) {
        $callCount++;

        return new Response($statusCode, [], new Stream('test'), 'OK');
    };

    // Test various status codes
    foreach ([200, 201, 204] as $code) {
        $statusCode = $code;
        $callCount = 0;

        $middleware->handle('GET', "/test-{$code}", [], [], $next);
        $middleware->handle('GET', "/test-{$code}", [], [], $next);

        expect($callCount)->toBe(1, "Status {$code} should be cached");
    }

    // Test non-2xx codes
    foreach ([301, 400, 404, 500] as $code) {
        $statusCode = $code;
        $callCount = 0;

        $middleware->handle('GET', "/test-{$code}", [], [], $next);
        $middleware->handle('GET', "/test-{$code}", [], [], $next);

        expect($callCount)->toBe(2, "Status {$code} should not be cached");
    }
});
