<?php

use Villaflor\Connection\Http\Response;
use Villaflor\Connection\Http\Stream;

it('can create response', function () {
    $stream = new Stream('test body');
    $response = new Response(200, ['Content-Type' => 'application/json'], $stream, 'OK');

    expect($response->getStatusCode())->toBe(200);
    expect($response->getReasonPhrase())->toBe('OK');
    expect((string) $response->getBody())->toBe('test body');
});

it('can get headers', function () {
    $stream = new Stream('test');
    $response = new Response(200, ['Content-Type' => 'application/json', 'X-Custom' => 'value'], $stream);

    expect($response->hasHeader('Content-Type'))->toBeTrue();
    expect($response->hasHeader('content-type'))->toBeTrue(); // case-insensitive
    expect($response->getHeaderLine('Content-Type'))->toBe('application/json');
    expect($response->getHeader('X-Custom'))->toBe(['value']);
});

it('can set headers immutably', function () {
    $stream = new Stream('test');
    $response = new Response(200, [], $stream);

    $newResponse = $response->withHeader('Content-Type', 'text/html');

    expect($response->hasHeader('Content-Type'))->toBeFalse();
    expect($newResponse->hasHeader('Content-Type'))->toBeTrue();
    expect($newResponse->getHeaderLine('Content-Type'))->toBe('text/html');
});

it('can add headers immutably', function () {
    $stream = new Stream('test');
    $response = new Response(200, ['X-Test' => 'value1'], $stream);

    $newResponse = $response->withAddedHeader('X-Test', 'value2');

    expect($newResponse->getHeader('X-Test'))->toBe(['value1', 'value2']);
});

it('can remove headers immutably', function () {
    $stream = new Stream('test');
    $response = new Response(200, ['Content-Type' => 'application/json'], $stream);

    $newResponse = $response->withoutHeader('Content-Type');

    expect($response->hasHeader('Content-Type'))->toBeTrue();
    expect($newResponse->hasHeader('Content-Type'))->toBeFalse();
});

it('can change status immutably', function () {
    $stream = new Stream('test');
    $response = new Response(200, [], $stream, 'OK');

    $newResponse = $response->withStatus(404, 'Not Found');

    expect($response->getStatusCode())->toBe(200);
    expect($newResponse->getStatusCode())->toBe(404);
    expect($newResponse->getReasonPhrase())->toBe('Not Found');
});

it('can change protocol version', function () {
    $stream = new Stream('test');
    $response = new Response(200, [], $stream);

    expect($response->getProtocolVersion())->toBe('1.1');

    $newResponse = $response->withProtocolVersion('2.0');
    expect($newResponse->getProtocolVersion())->toBe('2.0');
});

it('can change body immutably', function () {
    $stream1 = new Stream('original body');
    $stream2 = new Stream('new body');
    $response = new Response(200, [], $stream1);

    $newResponse = $response->withBody($stream2);

    expect((string) $response->getBody())->toBe('original body');
    expect((string) $newResponse->getBody())->toBe('new body');
});
