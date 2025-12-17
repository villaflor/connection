<?php

use Villaflor\Connection\Exception\TimeoutException;

it('can create timeout exception with default message', function () {
    $exception = new TimeoutException;

    expect($exception)->toBeInstanceOf(TimeoutException::class);
    expect($exception->getMessage())->toBe('Request timed out');
});

it('can create timeout exception with custom message', function () {
    $exception = new TimeoutException('Connection timeout', 30);

    expect($exception->getMessage())->toBe('Connection timeout');
    expect($exception->getCode())->toBe(30);
});
