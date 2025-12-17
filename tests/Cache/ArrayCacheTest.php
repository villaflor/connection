<?php

use Villaflor\Connection\Cache\ArrayCache;

test('can store and retrieve items', function () {
    $cache = new ArrayCache;

    $cache->set('key1', 'value1', 60);

    expect($cache->get('key1'))->toBe('value1');
});

test('returns null for non-existent keys', function () {
    $cache = new ArrayCache;

    expect($cache->get('nonexistent'))->toBeNull();
});

test('has returns true for existing keys', function () {
    $cache = new ArrayCache;

    $cache->set('key1', 'value1', 60);

    expect($cache->has('key1'))->toBeTrue();
});

test('has returns false for non-existent keys', function () {
    $cache = new ArrayCache;

    expect($cache->has('nonexistent'))->toBeFalse();
});

test('items expire after ttl', function () {
    $cache = new ArrayCache;

    // Set with 1 second TTL
    $cache->set('key1', 'value1', 1);

    expect($cache->has('key1'))->toBeTrue();

    // Wait for expiration
    sleep(2);

    expect($cache->has('key1'))->toBeFalse();
    expect($cache->get('key1'))->toBeNull();
});

test('can delete items', function () {
    $cache = new ArrayCache;

    $cache->set('key1', 'value1', 60);
    expect($cache->has('key1'))->toBeTrue();

    $cache->delete('key1');
    expect($cache->has('key1'))->toBeFalse();
});

test('can clear all items', function () {
    $cache = new ArrayCache;

    $cache->set('key1', 'value1', 60);
    $cache->set('key2', 'value2', 60);
    $cache->set('key3', 'value3', 60);

    expect($cache->count())->toBe(3);

    $cache->clear();

    expect($cache->count())->toBe(0);
    expect($cache->has('key1'))->toBeFalse();
    expect($cache->has('key2'))->toBeFalse();
    expect($cache->has('key3'))->toBeFalse();
});

test('count excludes expired items', function () {
    $cache = new ArrayCache;

    $cache->set('key1', 'value1', 1);  // Will expire
    $cache->set('key2', 'value2', 60); // Won't expire

    expect($cache->count())->toBe(2);

    sleep(2);

    expect($cache->count())->toBe(1);
});

test('can store different data types', function () {
    $cache = new ArrayCache;

    $cache->set('string', 'value', 60);
    $cache->set('int', 123, 60);
    $cache->set('array', ['a', 'b', 'c'], 60);
    $cache->set('object', (object) ['key' => 'value'], 60);

    expect($cache->get('string'))->toBe('value');
    expect($cache->get('int'))->toBe(123);
    expect($cache->get('array'))->toBe(['a', 'b', 'c']);
    expect($cache->get('object'))->toEqual((object) ['key' => 'value']);
});

test('overwrites existing keys', function () {
    $cache = new ArrayCache;

    $cache->set('key1', 'value1', 60);
    expect($cache->get('key1'))->toBe('value1');

    $cache->set('key1', 'value2', 60);
    expect($cache->get('key1'))->toBe('value2');
});
