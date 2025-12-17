<?php

use Villaflor\Connection\Cookie\Cookie;
use Villaflor\Connection\Cookie\CookieJar;

test('can add cookie to jar', function () {
    $jar = new CookieJar;
    $cookie = new Cookie('session_id', 'abc123');

    $jar->add($cookie);

    expect($jar->count())->toBe(1);
});

test('can add multiple cookies', function () {
    $jar = new CookieJar;

    $jar->add(new Cookie('session_id', 'abc123'));
    $jar->add(new Cookie('user_id', '456'));

    expect($jar->count())->toBe(2);
});

test('overwrites cookie with same name domain and path', function () {
    $jar = new CookieJar;

    $jar->add(new Cookie('session_id', 'old_value', domain: 'example.com'));
    $jar->add(new Cookie('session_id', 'new_value', domain: 'example.com'));

    expect($jar->count())->toBe(1);

    $cookies = $jar->all();
    expect($cookies[0]->getValue())->toBe('new_value');
});

test('stores cookies with different paths separately', function () {
    $jar = new CookieJar;

    $jar->add(new Cookie('token', 'value1', path: '/app'));
    $jar->add(new Cookie('token', 'value2', path: '/api'));

    expect($jar->count())->toBe(2);
});

test('can add cookies from Set-Cookie headers', function () {
    $jar = new CookieJar;

    $headers = [
        'session_id=abc123; Path=/; Domain=example.com',
        'user_id=456; Path=/app',
    ];

    $jar->addFromHeaders($headers);

    expect($jar->count())->toBe(2);
});

test('gets matching cookies for domain and path', function () {
    $jar = new CookieJar;

    $jar->add(new Cookie('cookie1', 'value1', domain: 'example.com', path: '/'));
    $jar->add(new Cookie('cookie2', 'value2', domain: 'example.com', path: '/app'));
    $jar->add(new Cookie('cookie3', 'value3', domain: 'other.com', path: '/'));

    $matching = $jar->getMatchingCookies('example.com', '/app');

    expect($matching)->toHaveCount(2);
    expect($matching[0]->getName())->toBe('cookie1');
    expect($matching[1]->getName())->toBe('cookie2');
});

test('does not return expired cookies', function () {
    $jar = new CookieJar;

    $jar->add(new Cookie('active', 'value1', expires: time() + 3600));
    $jar->add(new Cookie('expired', 'value2', expires: time() - 3600));

    $matching = $jar->getMatchingCookies('example.com', '/');

    expect($matching)->toHaveCount(1);
    expect($matching[0]->getName())->toBe('active');
});

test('gets cookie header for domain and path', function () {
    $jar = new CookieJar;

    $jar->add(new Cookie('session_id', 'abc123', domain: 'example.com'));
    $jar->add(new Cookie('user_id', '456', domain: 'example.com'));

    $header = $jar->getCookieHeader('example.com', '/');

    expect($header)->toContain('session_id=abc123');
    expect($header)->toContain('user_id=456');
    expect($header)->toContain('; ');
});

test('returns null when no matching cookies', function () {
    $jar = new CookieJar;

    $jar->add(new Cookie('session_id', 'abc123', domain: 'example.com'));

    $header = $jar->getCookieHeader('other.com', '/');

    expect($header)->toBeNull();
});

test('can clear all cookies', function () {
    $jar = new CookieJar;

    $jar->add(new Cookie('cookie1', 'value1'));
    $jar->add(new Cookie('cookie2', 'value2'));

    expect($jar->count())->toBe(2);

    $jar->clear();

    expect($jar->count())->toBe(0);
});

test('can remove expired cookies', function () {
    $jar = new CookieJar;

    $jar->add(new Cookie('active1', 'value1', expires: time() + 3600));
    $jar->add(new Cookie('expired1', 'value2', expires: time() - 3600));
    $jar->add(new Cookie('active2', 'value3', expires: time() + 7200));
    $jar->add(new Cookie('expired2', 'value4', expires: time() - 1800));

    expect($jar->count())->toBe(4);

    $jar->removeExpired();

    expect($jar->count())->toBe(2);

    $cookies = $jar->all();
    expect($cookies[0]->getName())->toBe('active1');
    expect($cookies[1]->getName())->toBe('active2');
});

test('all returns array of all cookies', function () {
    $jar = new CookieJar;

    $jar->add(new Cookie('cookie1', 'value1'));
    $jar->add(new Cookie('cookie2', 'value2'));
    $jar->add(new Cookie('cookie3', 'value3'));

    $all = $jar->all();

    expect($all)->toBeArray();
    expect($all)->toHaveCount(3);
    expect($all[0])->toBeInstanceOf(Cookie::class);
});

test('handles cookies with no domain or path', function () {
    $jar = new CookieJar;

    $jar->add(new Cookie('session_id', 'abc123'));

    $matching = $jar->getMatchingCookies('any.domain.com', '/any/path');

    expect($matching)->toHaveCount(1);
});

test('cookie header combines multiple cookies with semicolon', function () {
    $jar = new CookieJar;

    $jar->add(new Cookie('cookie1', 'value1'));
    $jar->add(new Cookie('cookie2', 'value2'));
    $jar->add(new Cookie('cookie3', 'value3'));

    $header = $jar->getCookieHeader('example.com', '/');

    expect($header)->toBe('cookie1=value1; cookie2=value2; cookie3=value3');
});
