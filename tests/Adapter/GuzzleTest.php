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

    $this->client = new Guzzle($auth, 'https://postman-echo.com/');
});

it('can GET request', function () {
    $response = $this->client->get('https://postman-echo.com/get');

    $headers = $response->getHeaders();
    $this->assertEquals('application/json; charset=utf-8', $headers['Content-Type'][0]);

    $body = json_decode($response->getBody());
    $this->assertEquals('Test', $body->headers->{'x-testing'});

    $response = $this->client->get('https://postman-echo.com/get', [], ['X-Another-Test' => 'Test2']);
    $body = json_decode($response->getBody());
    $this->assertEquals('Test2', $body->headers->{'x-another-test'});
});

it('can POST request', function () {
    $response = $this->client->post('https://postman-echo.com/post', ['X-Post-Test' => 'Testing a POST request.']);

    $headers = $response->getHeaders();
    $this->assertEquals('application/json; charset=utf-8', $headers['Content-Type'][0]);

    $body = json_decode($response->getBody());
    $this->assertEquals('Testing a POST request.', $body->json->{'X-Post-Test'});
});

it('can PUT request', function () {
    $response = $this->client->put('https://postman-echo.com/put', ['X-Put-Test' => 'Testing a PUT request.']);

    $headers = $response->getHeaders();
    $this->assertEquals('application/json; charset=utf-8', $headers['Content-Type'][0]);

    $body = json_decode($response->getBody());
    $this->assertEquals('Testing a PUT request.', $body->json->{'X-Put-Test'});
});

it('can PATCH request', function () {
    $response = $this->client->patch(
        'https://postman-echo.com/patch',
        ['X-Patch-Test' => 'Testing a PATCH request.']
    );

    $headers = $response->getHeaders();
    $this->assertEquals('application/json; charset=utf-8', $headers['Content-Type'][0]);

    $body = json_decode($response->getBody());
    $this->assertEquals('Testing a PATCH request.', $body->json->{'X-Patch-Test'});
});

it('can DELETE request', function () {
    $response = $this->client->delete(
        'https://postman-echo.com/delete',
        ['X-Delete-Test' => 'Testing a DELETE request.']
    );

    $headers = $response->getHeaders();
    $this->assertEquals('application/json; charset=utf-8', $headers['Content-Type'][0]);

    $body = json_decode($response->getBody());
    $this->assertEquals('Testing a DELETE request.', $body->json->{'X-Delete-Test'});
});

it('can POST request form params', function () {
    $response = $this->client->post('https://postman-echo.com/post', ['form_params' => ['X-Post-Test' => 'Testing a POST request.']]);

    $body = json_decode($response->getBody());
    $this->assertEquals('Testing a POST request.', $body->form->{'X-Post-Test'});
});

it('can Not Found request', function () {
    $this->expectException(ResponseException::class);
    $this->client->get('https://postman-echo.com/status/404');
});

it('can Server Error request', function () {
    $this->expectException(ResponseException::class);
    $this->client->get('https://postman-echo.com/status/500');
});

it('can validate valid request method', function () {
    $this->expectException(InvalidArgumentException::class);
    $this->client->request('mark', 'https://postman-echo.com/status/500');
});
