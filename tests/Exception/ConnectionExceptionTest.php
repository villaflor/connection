<?php

use Villaflor\Connection\Exception\ConnectionException;

it('can create connection exception', function () {
    $exception = new ConnectionException('Connection failed', 6);

    expect($exception)->toBeInstanceOf(ConnectionException::class);
    expect($exception->getMessage())->toBe('Connection failed');
    expect($exception->getCode())->toBe(6);
});

it('can create connection exception with previous', function () {
    $previous = new Exception('Previous error');
    $exception = new ConnectionException('Connection failed', 6, $previous);

    expect($exception->getPrevious())->toBe($previous);
});
