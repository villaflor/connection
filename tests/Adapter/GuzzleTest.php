<?php

use Villaflor\Connection\Adapter\Guzzle;
use Villaflor\Connection\Auth\AuthInterface;
use Villaflor\Connection\Exception\ResponseException;

beforeEach(function () {
    $auth = $this->getMockBuilder(AuthInterface::class)
        ->onlyMethods(['getHeaders'])
        ->getMock();

    $auth->method('getHeaders')
        ->willReturn(['X-Testing' => 'Test']);

    $this->client = new Guzzle($auth, 'https://httpbin.org/');
});

it('can GET request', function () {
    $response = $this->client->get('https://httpbin.org/get');

    $headers = $response->getHeaders();
    $this->assertEquals('application/json', $headers['Content-Type'][0]);

    $body = json_decode($response->getBody());
    $this->assertEquals('Test', $body->headers->{'X-Testing'});

    $response = $this->client->get('https://httpbin.org/get', [], ['X-Another-Test' => 'Test2']);
    $body = json_decode($response->getBody());
    $this->assertEquals('Test2', $body->headers->{'X-Another-Test'});
});

it('can POST request', function () {
    $response = $this->client->post('https://httpbin.org/post', ['X-Post-Test' => 'Testing a POST request.']);

    $headers = $response->getHeaders();
    $this->assertEquals('application/json', $headers['Content-Type'][0]);

    $body = json_decode($response->getBody());
    $this->assertEquals('Testing a POST request.', $body->json->{'X-Post-Test'});
});

it('can PUT request', function () {
    $response = $this->client->put('https://httpbin.org/put', ['X-Put-Test' => 'Testing a PUT request.']);

    $headers = $response->getHeaders();
    $this->assertEquals('application/json', $headers['Content-Type'][0]);

    $body = json_decode($response->getBody());
    $this->assertEquals('Testing a PUT request.', $body->json->{'X-Put-Test'});
});

it('can PATCH request', function () {
    $response = $this->client->patch(
        'https://httpbin.org/patch',
        ['X-Patch-Test' => 'Testing a PATCH request.']
    );

    $headers = $response->getHeaders();
    $this->assertEquals('application/json', $headers['Content-Type'][0]);

    $body = json_decode($response->getBody());
    $this->assertEquals('Testing a PATCH request.', $body->json->{'X-Patch-Test'});
});

it('can DELETE request', function () {
    $response = $this->client->delete(
        'https://httpbin.org/delete',
        ['X-Delete-Test' => 'Testing a DELETE request.']
    );

    $headers = $response->getHeaders();
    $this->assertEquals('application/json', $headers['Content-Type'][0]);

    $body = json_decode($response->getBody());
    $this->assertEquals('Testing a DELETE request.', $body->json->{'X-Delete-Test'});
});

it('can POST request form params', function () {
    $response = $this->client->post('https://httpbin.org/post', ['form_params' => ['X-Post-Test' => 'Testing a POST request.']]);

    $body = json_decode($response->getBody());
    $this->assertEquals('Testing a POST request.', $body->form->{'X-Post-Test'});
});

it('can Not Found request', function () {
    $this->expectException(ResponseException::class);
    $this->client->get('https://httpbin.org/status/404');
});

it('can Server Error request', function () {
    $this->expectException(ResponseException::class);
    $this->client->get('https://httpbin.org/status/500');
});

it('can validate valid request method', function () {
    $this->expectException(InvalidArgumentException::class);
    $this->client->request('mark', 'https://httpbin.org/status/500');
});
