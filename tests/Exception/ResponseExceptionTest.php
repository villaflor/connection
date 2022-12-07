<?php

use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Villaflor\Connection\Exception\JSONException;
use Villaflor\Connection\Exception\ResponseException;

it('can From Request Exception No Response', function () {
    $reqErr = new RequestException('foo', new Request('GET', '/test'));
    $respErr = ResponseException::fromRequestException($reqErr);

    $this->assertInstanceOf(ResponseException::class, $respErr);
    $this->assertEquals($reqErr->getMessage(), $respErr->getMessage());
    $this->assertEquals(0, $respErr->getCode());
    $this->assertEquals($reqErr, $respErr->getPrevious());
});

it('can From Request Exception Empty Content Type', function () {
    $resp = new Response(404);
    $reqErr = new RequestException('foo', new Request('GET', '/test'), $resp);
    $respErr = ResponseException::fromRequestException($reqErr);

    $this->assertInstanceOf(ResponseException::class, $respErr);
    $this->assertEquals($reqErr->getMessage(), $respErr->getMessage());
    $this->assertEquals(0, $respErr->getCode());
    $this->assertEquals($reqErr, $respErr->getPrevious());
});

it('can From Request Exception Unknown Content Type', function () {
    $resp = new Response(404, ['Content-Type' => ['application/octet-stream']]);
    $reqErr = new RequestException('foo', new Request('GET', '/test'), $resp);
    $respErr = ResponseException::fromRequestException($reqErr);

    $this->assertInstanceOf(ResponseException::class, $respErr);
    $this->assertEquals($reqErr->getMessage(), $respErr->getMessage());
    $this->assertEquals(0, $respErr->getCode());
    $this->assertEquals($reqErr, $respErr->getPrevious());
});

it('can From Request Exception JSON Decode Error', function () {
    $resp = new Response(404, ['Content-Type' => ['application/json; charset=utf-8']], '[what]');
    $reqErr = new RequestException('foo', new Request('GET', '/test'), $resp);
    $respErr = ResponseException::fromRequestException($reqErr);

    $this->assertInstanceOf(ResponseException::class, $respErr);
    $this->assertEquals($reqErr->getMessage(), $respErr->getMessage());
    $this->assertEquals(0, $respErr->getCode());
    $this->assertInstanceOf(JSONException::class, $respErr->getPrevious());
    $this->assertEquals($reqErr, $respErr->getPrevious()->getPrevious());
});

it('can From Request Exception JSON With Errors', function () {
    $body = '{
          "result": null,
          "success": false,
          "errors": [{"code":1003, "message":"This is an error"}],
          "messages": []
        }';

    $resp = new Response(404, ['Content-Type' => ['application/json; charset=utf-8']], $body);
    $reqErr = new RequestException('foo', new Request('GET', '/test'), $resp);
    $respErr = ResponseException::fromRequestException($reqErr);

    $this->assertInstanceOf(ResponseException::class, $respErr);
    $this->assertEquals('This is an error', $respErr->getMessage());
    $this->assertEquals(1003, $respErr->getCode());
    $this->assertEquals($reqErr, $respErr->getPrevious());
});
