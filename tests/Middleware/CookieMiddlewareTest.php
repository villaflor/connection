<?php

use Villaflor\Connection\Cookie\Cookie;
use Villaflor\Connection\Cookie\CookieJar;
use Villaflor\Connection\Http\Response;
use Villaflor\Connection\Http\Stream;
use Villaflor\Connection\Middleware\CookieMiddleware;

test('adds cookies from jar to request', function () {
    $jar = new CookieJar;
    $jar->add(new Cookie('session_id', 'abc123', domain: 'example.com'));

    $middleware = new CookieMiddleware($jar);

    $capturedHeaders = null;
    $next = function ($method, $uri, $data, $headers) use (&$capturedHeaders) {
        $capturedHeaders = $headers;

        return new Response(200, [], new Stream('test'), 'OK');
    };

    $middleware->handle('GET', 'https://example.com/test', [], [], $next);

    expect($capturedHeaders)->toHaveKey('Cookie');
    expect($capturedHeaders['Cookie'])->toBe('session_id=abc123');
});

test('does not add Cookie header when no matching cookies', function () {
    $jar = new CookieJar;
    $jar->add(new Cookie('session_id', 'abc123', domain: 'other.com'));

    $middleware = new CookieMiddleware($jar);

    $capturedHeaders = null;
    $next = function ($method, $uri, $data, $headers) use (&$capturedHeaders) {
        $capturedHeaders = $headers;

        return new Response(200, [], new Stream('test'), 'OK');
    };

    $middleware->handle('GET', 'https://example.com/test', [], [], $next);

    expect($capturedHeaders)->not->toHaveKey('Cookie');
});

test('stores cookies from Set-Cookie response header', function () {
    $jar = new CookieJar;
    $middleware = new CookieMiddleware($jar);

    $next = function ($method, $uri, $data, $headers) {
        return new Response(
            200,
            ['Set-Cookie' => ['session_id=abc123; Path=/; Domain=example.com']],
            new Stream('test'),
            'OK'
        );
    };

    $middleware->handle('GET', 'https://example.com/test', [], [], $next);

    expect($jar->count())->toBe(1);

    $cookies = $jar->all();
    expect($cookies[0]->getName())->toBe('session_id');
    expect($cookies[0]->getValue())->toBe('abc123');
});

test('stores multiple cookies from Set-Cookie headers', function () {
    $jar = new CookieJar;
    $middleware = new CookieMiddleware($jar);

    $next = function ($method, $uri, $data, $headers) {
        return new Response(
            200,
            [
                'Set-Cookie' => [
                    'session_id=abc123; Path=/; Domain=example.com',
                    'user_id=456; Path=/; Domain=example.com',
                ],
            ],
            new Stream('test'),
            'OK'
        );
    };

    $middleware->handle('GET', 'https://example.com/test', [], [], $next);

    expect($jar->count())->toBe(2);
});

test('does not modify jar when no Set-Cookie header', function () {
    $jar = new CookieJar;
    $jar->add(new Cookie('existing', 'value'));

    $middleware = new CookieMiddleware($jar);

    $next = function ($method, $uri, $data, $headers) {
        return new Response(200, [], new Stream('test'), 'OK');
    };

    $middleware->handle('GET', 'https://example.com/test', [], [], $next);

    expect($jar->count())->toBe(1);
});

test('combines request cookies with domain-specific matching', function () {
    $jar = new CookieJar;
    $jar->add(new Cookie('cookie1', 'value1', domain: 'example.com', path: '/'));
    $jar->add(new Cookie('cookie2', 'value2', domain: 'example.com', path: '/api'));
    $jar->add(new Cookie('cookie3', 'value3', domain: 'other.com'));

    $middleware = new CookieMiddleware($jar);

    $capturedHeaders = null;
    $next = function ($method, $uri, $data, $headers) use (&$capturedHeaders) {
        $capturedHeaders = $headers;

        return new Response(200, [], new Stream('test'), 'OK');
    };

    // Request to /api should get both cookie1 (path=/) and cookie2 (path=/api)
    $middleware->handle('GET', 'https://example.com/api/endpoint', [], [], $next);

    expect($capturedHeaders['Cookie'])->toContain('cookie1=value1');
    expect($capturedHeaders['Cookie'])->toContain('cookie2=value2');
    expect($capturedHeaders['Cookie'])->not->toContain('cookie3');
});

test('can get cookie jar from middleware', function () {
    $jar = new CookieJar;
    $middleware = new CookieMiddleware($jar);

    expect($middleware->getCookieJar())->toBe($jar);
});

test('handles URIs without path', function () {
    $jar = new CookieJar;
    $jar->add(new Cookie('session_id', 'abc123', domain: 'example.com'));

    $middleware = new CookieMiddleware($jar);

    $capturedHeaders = null;
    $next = function ($method, $uri, $data, $headers) use (&$capturedHeaders) {
        $capturedHeaders = $headers;

        return new Response(200, [], new Stream('test'), 'OK');
    };

    $middleware->handle('GET', 'https://example.com', [], [], $next);

    expect($capturedHeaders)->toHaveKey('Cookie');
    expect($capturedHeaders['Cookie'])->toBe('session_id=abc123');
});

test('persists cookies across multiple requests', function () {
    $jar = new CookieJar;
    $middleware = new CookieMiddleware($jar);

    // First request sets a cookie
    $next1 = function ($method, $uri, $data, $headers) {
        return new Response(
            200,
            ['Set-Cookie' => ['session_id=abc123; Domain=example.com']],
            new Stream('test'),
            'OK'
        );
    };

    $middleware->handle('GET', 'https://example.com/login', [], [], $next1);

    // Second request should include the cookie
    $capturedHeaders = null;
    $next2 = function ($method, $uri, $data, $headers) use (&$capturedHeaders) {
        $capturedHeaders = $headers;

        return new Response(200, [], new Stream('test'), 'OK');
    };

    $middleware->handle('GET', 'https://example.com/dashboard', [], [], $next2);

    expect($capturedHeaders)->toHaveKey('Cookie');
    expect($capturedHeaders['Cookie'])->toBe('session_id=abc123');
});
