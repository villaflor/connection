<?php

use Villaflor\Connection\Cookie\Cookie;

test('can create cookie with basic attributes', function () {
    $cookie = new Cookie('session_id', 'abc123');

    expect($cookie->getName())->toBe('session_id');
    expect($cookie->getValue())->toBe('abc123');
    expect($cookie->getExpires())->toBeNull();
    expect($cookie->getPath())->toBeNull();
    expect($cookie->getDomain())->toBeNull();
    expect($cookie->isSecure())->toBeFalse();
    expect($cookie->isHttpOnly())->toBeFalse();
});

test('can create cookie with all attributes', function () {
    $expires = time() + 3600;
    $cookie = new Cookie(
        name: 'session_id',
        value: 'abc123',
        expires: $expires,
        path: '/app',
        domain: 'example.com',
        secure: true,
        httpOnly: true
    );

    expect($cookie->getName())->toBe('session_id');
    expect($cookie->getValue())->toBe('abc123');
    expect($cookie->getExpires())->toBe($expires);
    expect($cookie->getPath())->toBe('/app');
    expect($cookie->getDomain())->toBe('example.com');
    expect($cookie->isSecure())->toBeTrue();
    expect($cookie->isHttpOnly())->toBeTrue();
});

test('can parse simple Set-Cookie header', function () {
    $cookie = Cookie::fromSetCookieHeader('session_id=abc123');

    expect($cookie->getName())->toBe('session_id');
    expect($cookie->getValue())->toBe('abc123');
});

test('can parse Set-Cookie header with path', function () {
    $cookie = Cookie::fromSetCookieHeader('session_id=abc123; Path=/app');

    expect($cookie->getName())->toBe('session_id');
    expect($cookie->getValue())->toBe('abc123');
    expect($cookie->getPath())->toBe('/app');
});

test('can parse Set-Cookie header with domain', function () {
    $cookie = Cookie::fromSetCookieHeader('session_id=abc123; Domain=example.com');

    expect($cookie->getName())->toBe('session_id');
    expect($cookie->getDomain())->toBe('example.com');
});

test('strips leading dot from domain', function () {
    $cookie = Cookie::fromSetCookieHeader('session_id=abc123; Domain=.example.com');

    expect($cookie->getDomain())->toBe('example.com');
});

test('can parse Set-Cookie header with expires', function () {
    $expiresDate = 'Wed, 21 Oct 2025 07:28:00 GMT';
    $cookie = Cookie::fromSetCookieHeader("session_id=abc123; Expires={$expiresDate}");

    expect($cookie->getExpires())->toBe(strtotime($expiresDate));
});

test('can parse Set-Cookie header with max-age', function () {
    $maxAge = 3600;
    $cookie = Cookie::fromSetCookieHeader("session_id=abc123; Max-Age={$maxAge}");

    expect($cookie->getExpires())->toBeGreaterThanOrEqual(time() + $maxAge - 1);
    expect($cookie->getExpires())->toBeLessThanOrEqual(time() + $maxAge + 1);
});

test('can parse Set-Cookie header with secure flag', function () {
    $cookie = Cookie::fromSetCookieHeader('session_id=abc123; Secure');

    expect($cookie->isSecure())->toBeTrue();
});

test('can parse Set-Cookie header with httponly flag', function () {
    $cookie = Cookie::fromSetCookieHeader('session_id=abc123; HttpOnly');

    expect($cookie->isHttpOnly())->toBeTrue();
});

test('can parse complex Set-Cookie header', function () {
    $cookie = Cookie::fromSetCookieHeader(
        'session_id=abc123; Domain=example.com; Path=/app; Expires=Wed, 21 Oct 2025 07:28:00 GMT; Secure; HttpOnly'
    );

    expect($cookie->getName())->toBe('session_id');
    expect($cookie->getValue())->toBe('abc123');
    expect($cookie->getDomain())->toBe('example.com');
    expect($cookie->getPath())->toBe('/app');
    expect($cookie->isSecure())->toBeTrue();
    expect($cookie->isHttpOnly())->toBeTrue();
});

test('cookie without expiry is not expired', function () {
    $cookie = new Cookie('session_id', 'abc123');

    expect($cookie->isExpired())->toBeFalse();
});

test('cookie with future expiry is not expired', function () {
    $cookie = new Cookie('session_id', 'abc123', expires: time() + 3600);

    expect($cookie->isExpired())->toBeFalse();
});

test('cookie with past expiry is expired', function () {
    $cookie = new Cookie('session_id', 'abc123', expires: time() - 3600);

    expect($cookie->isExpired())->toBeTrue();
});

test('cookie matches exact domain', function () {
    $cookie = new Cookie('session_id', 'abc123', domain: 'example.com');

    expect($cookie->matches('example.com', '/'))->toBeTrue();
});

test('cookie matches subdomain', function () {
    $cookie = new Cookie('session_id', 'abc123', domain: 'example.com');

    expect($cookie->matches('sub.example.com', '/'))->toBeTrue();
});

test('cookie does not match different domain', function () {
    $cookie = new Cookie('session_id', 'abc123', domain: 'example.com');

    expect($cookie->matches('other.com', '/'))->toBeFalse();
});

test('cookie matches exact path', function () {
    $cookie = new Cookie('session_id', 'abc123', path: '/app');

    expect($cookie->matches('example.com', '/app'))->toBeTrue();
});

test('cookie matches subpath', function () {
    $cookie = new Cookie('session_id', 'abc123', path: '/app');

    expect($cookie->matches('example.com', '/app/dashboard'))->toBeTrue();
});

test('cookie does not match parent path', function () {
    $cookie = new Cookie('session_id', 'abc123', path: '/app');

    expect($cookie->matches('example.com', '/'))->toBeFalse();
});

test('cookie without domain or path matches everything', function () {
    $cookie = new Cookie('session_id', 'abc123');

    expect($cookie->matches('example.com', '/'))->toBeTrue();
    expect($cookie->matches('other.com', '/any/path'))->toBeTrue();
});

test('converts cookie to string', function () {
    $cookie = new Cookie('session_id', 'abc123');

    expect($cookie->toString())->toBe('session_id=abc123');
});

test('toString includes only name and value', function () {
    $cookie = new Cookie(
        name: 'session_id',
        value: 'abc123',
        expires: time() + 3600,
        path: '/app',
        domain: 'example.com',
        secure: true,
        httpOnly: true
    );

    // toString should only include name=value, not other attributes
    expect($cookie->toString())->toBe('session_id=abc123');
});

test('ignores unknown cookie attributes', function () {
    // Include SameSite and other attributes we don't handle
    $cookie = Cookie::fromSetCookieHeader(
        'session_id=abc123; SameSite=Strict; UnknownAttr=value; AnotherFlag; Path=/test'
    );

    // Should parse what we understand and ignore the rest
    expect($cookie->getName())->toBe('session_id');
    expect($cookie->getValue())->toBe('abc123');
    expect($cookie->getPath())->toBe('/test');
});
