<?php

use Villaflor\Connection\Exception\InvalidResponseException;

it('can create invalid response exception with default message', function () {
    $exception = new InvalidResponseException;

    expect($exception)->toBeInstanceOf(InvalidResponseException::class);
    expect($exception->getMessage())->toBe('Invalid response received');
});

it('can create invalid response exception with custom message', function () {
    $exception = new InvalidResponseException('JSON parsing failed');

    expect($exception->getMessage())->toBe('JSON parsing failed');
});

it('can create invalid response exception with previous', function () {
    $previous = new Exception('Parse error');
    $exception = new InvalidResponseException('JSON parsing failed', $previous);

    expect($exception->getPrevious())->toBe($previous);
});
