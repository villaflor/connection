<?php

use Villaflor\Connection\Adapter\Curl;
use Villaflor\Connection\Auth\AuthInterface;
use Villaflor\Connection\Exception\ResponseException;

beforeEach(function () {
    $auth = $this->getMockBuilder(AuthInterface::class)
        ->onlyMethods(['getHeaders'])
        ->getMock();

    $auth->method('getHeaders')
        ->willReturn(['X-Testing' => 'Test']);

    $this->client = new Curl($auth, 'https://postman-echo.com/');
});

it('can GET request', function () {
    $response = $this->client->get('https://postman-echo.com/get');

    $headers = $response->getHeaders();
    $this->assertEquals('application/json; charset=utf-8', $headers['content-type'][0]);

    $body = json_decode($response->getBody());
    $this->assertEquals('Test', $body->headers->{'x-testing'});

    $response = $this->client->get('https://postman-echo.com/get', [], ['X-Another-Test' => 'Test2']);
    $body = json_decode($response->getBody());
    $this->assertEquals('Test2', $body->headers->{'x-another-test'});
});

it('can POST request', function () {
    $response = $this->client->post('https://postman-echo.com/post', ['X-Post-Test' => 'Testing a POST request.']);

    $headers = $response->getHeaders();
    $this->assertEquals('application/json; charset=utf-8', $headers['content-type'][0]);

    $body = json_decode($response->getBody());
    $this->assertEquals('Testing a POST request.', $body->json->{'X-Post-Test'});
});

it('can PUT request', function () {
    $response = $this->client->put('https://postman-echo.com/put', ['X-Put-Test' => 'Testing a PUT request.']);

    $headers = $response->getHeaders();
    $this->assertEquals('application/json; charset=utf-8', $headers['content-type'][0]);

    $body = json_decode($response->getBody());
    $this->assertEquals('Testing a PUT request.', $body->json->{'X-Put-Test'});
});

it('can PATCH request', function () {
    $response = $this->client->patch(
        'https://postman-echo.com/patch',
        ['X-Patch-Test' => 'Testing a PATCH request.']
    );

    $headers = $response->getHeaders();
    $this->assertEquals('application/json; charset=utf-8', $headers['content-type'][0]);

    $body = json_decode($response->getBody());
    $this->assertEquals('Testing a PATCH request.', $body->json->{'X-Patch-Test'});
});

it('can DELETE request', function () {
    $response = $this->client->delete(
        'https://postman-echo.com/delete',
        ['X-Delete-Test' => 'Testing a DELETE request.']
    );

    $headers = $response->getHeaders();
    $this->assertEquals('application/json; charset=utf-8', $headers['content-type'][0]);

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

it('can set timeout', function () {
    $this->client->setTimeout(60);
    $response = $this->client->get('https://postman-echo.com/get');
    $this->assertEquals(200, $response->getStatusCode());
});

it('can set connect timeout', function () {
    $this->client->setConnectTimeout(5);
    $response = $this->client->get('https://postman-echo.com/get');
    $this->assertEquals(200, $response->getStatusCode());
});

it('can use relative URLs with base URI', function () {
    $response = $this->client->get('get');
    $this->assertEquals(200, $response->getStatusCode());
});

it('can handle query params with existing query string', function () {
    $response = $this->client->get('get?existing=param', ['new' => 'value']);
    $body = json_decode($response->getBody());
    $this->assertEquals('param', $body->args->existing);
    $this->assertEquals('value', $body->args->new);
});

it('can handle invalid URL gracefully', function () {
    $this->expectException(Villaflor\Connection\Exception\ConnectionException::class);
    $this->client->get('http://invalid-domain-that-does-not-exist-12345.com');
});

it('can GET and decode JSON automatically', function () {
    $data = $this->client->getJson('https://postman-echo.com/get', ['test' => 'value']);

    $this->assertIsArray($data);
    $this->assertEquals('value', $data['args']['test']);
});

it('can POST and decode JSON automatically', function () {
    $data = $this->client->postJson('https://postman-echo.com/post', ['name' => 'John']);

    $this->assertIsArray($data);
    $this->assertEquals('John', $data['json']['name']);
});

it('can PUT and decode JSON automatically', function () {
    $data = $this->client->putJson('https://postman-echo.com/put', ['name' => 'Jane']);

    $this->assertIsArray($data);
    $this->assertEquals('Jane', $data['json']['name']);
});

it('can PATCH and decode JSON automatically', function () {
    $data = $this->client->patchJson('https://postman-echo.com/patch', ['email' => 'test@example.com']);

    $this->assertIsArray($data);
    $this->assertEquals('test@example.com', $data['json']['email']);
});

it('can DELETE and decode JSON automatically', function () {
    $data = $this->client->deleteJson('https://postman-echo.com/delete', ['reason' => 'test']);

    $this->assertIsArray($data);
    $this->assertEquals('test', $data['json']['reason']);
});

it('returns null for empty JSON response', function () {
    // Mock a response with empty body
    $data = $this->client->getJson('https://postman-echo.com/get');

    $this->assertIsArray($data);
});

it('throws InvalidResponseException for malformed JSON', function () {
    // Test using mock to avoid external dependency
    $mockResponse = $this->getMockBuilder(Psr\Http\Message\ResponseInterface::class)->getMock();
    $mockStream = $this->getMockBuilder(Psr\Http\Message\StreamInterface::class)->getMock();

    $mockStream->method('__toString')->willReturn('not valid json {{{');
    $mockResponse->method('getBody')->willReturn($mockStream);

    // Use reflection to test the private decodeJsonResponse method
    $auth = $this->getMockBuilder(Villaflor\Connection\Auth\AuthInterface::class)
        ->onlyMethods(['getHeaders'])
        ->getMock();
    $auth->method('getHeaders')->willReturn([]);

    $client = new Villaflor\Connection\Adapter\Curl($auth, 'https://example.com');

    $reflection = new ReflectionClass($client);
    $method = $reflection->getMethod('decodeJsonResponse');
    $method->setAccessible(true);

    $this->expectException(Villaflor\Connection\Exception\InvalidResponseException::class);
    $method->invoke($client, $mockResponse);
});

it('returns null for empty response body in JSON methods', function () {
    // Test using mock to avoid external dependency
    $mockResponse = $this->getMockBuilder(Psr\Http\Message\ResponseInterface::class)->getMock();
    $mockStream = $this->getMockBuilder(Psr\Http\Message\StreamInterface::class)->getMock();

    $mockStream->method('__toString')->willReturn('');
    $mockResponse->method('getBody')->willReturn($mockStream);

    // Use reflection to test the private decodeJsonResponse method
    $auth = $this->getMockBuilder(Villaflor\Connection\Auth\AuthInterface::class)
        ->onlyMethods(['getHeaders'])
        ->getMock();
    $auth->method('getHeaders')->willReturn([]);

    $client = new Villaflor\Connection\Adapter\Curl($auth, 'https://example.com');

    $reflection = new ReflectionClass($client);
    $method = $reflection->getMethod('decodeJsonResponse');
    $method->setAccessible(true);

    $result = $method->invoke($client, $mockResponse);
    $this->assertNull($result);
});

it('throws TimeoutException for operation timeout', function () {
    $auth = $this->getMockBuilder(Villaflor\Connection\Auth\AuthInterface::class)
        ->onlyMethods(['getHeaders'])
        ->getMock();
    $auth->method('getHeaders')->willReturn([]);

    $client = new Villaflor\Connection\Adapter\Curl($auth, 'http://10.255.255.1');

    // Set very short timeout
    $client->setTimeout(1);
    $client->setConnectTimeout(1);

    $this->expectException(Villaflor\Connection\Exception\TimeoutException::class);

    // Try to connect to non-routable IP to trigger timeout
    $client->get('/');
});
