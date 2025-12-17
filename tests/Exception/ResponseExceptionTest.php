<?php

use Villaflor\Connection\Exception\JSONException;
use Villaflor\Connection\Exception\ResponseException;
use Villaflor\Connection\Http\Response;
use Villaflor\Connection\Http\Stream;

it('can create exception from response with empty content type', function () {
    $resp = new Response(404, [], new Stream(''), 'Not Found');
    $respErr = ResponseException::fromResponse($resp);

    expect($respErr)->toBeInstanceOf(ResponseException::class);
    expect($respErr->getMessage())->toBe('HTTP 404: Not Found');
    expect($respErr->getCode())->toBe(404);
});

it('can create exception from response with unknown content type', function () {
    $resp = new Response(404, ['Content-Type' => 'application/octet-stream'], new Stream('binary data'), 'Not Found');
    $respErr = ResponseException::fromResponse($resp);

    expect($respErr)->toBeInstanceOf(ResponseException::class);
    expect($respErr->getMessage())->toBe('HTTP 404: Not Found - binary data');
    expect($respErr->getCode())->toBe(404);
});

it('can create exception from response with JSON decode error', function () {
    $resp = new Response(
        404,
        ['Content-Type' => 'application/json; charset=utf-8'],
        new Stream('[what]'),
        'Not Found'
    );
    $respErr = ResponseException::fromResponse($resp);

    expect($respErr)->toBeInstanceOf(ResponseException::class);
    expect($respErr->getMessage())->toContain('JSON decode error');
    expect($respErr->getCode())->toBe(404);
    expect($respErr->getPrevious())->toBeInstanceOf(JSONException::class);
});

it('can create exception from response with JSON errors array', function () {
    $body = '{
          "result": null,
          "success": false,
          "errors": [{"code":1003, "message":"This is an error"}],
          "messages": []
        }';

    $resp = new Response(
        404,
        ['Content-Type' => 'application/json; charset=utf-8'],
        new Stream($body),
        'Not Found'
    );
    $respErr = ResponseException::fromResponse($resp);

    expect($respErr)->toBeInstanceOf(ResponseException::class);
    expect($respErr->getMessage())->toBe('This is an error');
    expect($respErr->getCode())->toBe(1003);
});

it('can create exception from response with JSON message field', function () {
    $body = '{"message":"Custom error message"}';

    $resp = new Response(
        400,
        ['Content-Type' => 'application/json'],
        new Stream($body),
        'Bad Request'
    );
    $respErr = ResponseException::fromResponse($resp);

    expect($respErr)->toBeInstanceOf(ResponseException::class);
    expect($respErr->getMessage())->toBe('Custom error message');
    expect($respErr->getCode())->toBe(400);
});

it('can create exception from response with JSON error field (string)', function () {
    $body = '{"error":"Something went wrong"}';

    $resp = new Response(
        500,
        ['Content-Type' => 'application/json'],
        new Stream($body),
        'Internal Server Error'
    );
    $respErr = ResponseException::fromResponse($resp);

    expect($respErr)->toBeInstanceOf(ResponseException::class);
    expect($respErr->getMessage())->toBe('Something went wrong');
    expect($respErr->getCode())->toBe(500);
});

it('can create exception from response with JSON error field (object)', function () {
    $body = '{"error":{"message":"Detailed error"}}';

    $resp = new Response(
        422,
        ['Content-Type' => 'application/json'],
        new Stream($body),
        'Unprocessable Entity'
    );
    $respErr = ResponseException::fromResponse($resp);

    expect($respErr)->toBeInstanceOf(ResponseException::class);
    expect($respErr->getMessage())->toBe('Detailed error');
    expect($respErr->getCode())->toBe(422);
});
