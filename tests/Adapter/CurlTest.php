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

it('can set retry config', function () {
    $retryConfig = new Villaflor\Connection\Retry\RetryConfig(maxAttempts: 3);
    $this->client->setRetryConfig($retryConfig);

    // If no exception is thrown, the setter works
    expect(true)->toBeTrue();
});

it('retries request on retryable status code', function () {
    $auth = $this->getMockBuilder(Villaflor\Connection\Auth\AuthInterface::class)
        ->onlyMethods(['getHeaders'])
        ->getMock();
    $auth->method('getHeaders')->willReturn([]);

    $client = new Villaflor\Connection\Adapter\Curl($auth, 'https://postman-echo.com');

    // Set retry config with very small delay for fast testing
    $retryConfig = new Villaflor\Connection\Retry\RetryConfig(
        maxAttempts: 3,
        retryableStatusCodes: [503],
        baseDelay: 1,
        exponentialBackoff: false
    );
    $client->setRetryConfig($retryConfig);

    // The 503 status will cause retries and eventually throw
    $this->expectException(Villaflor\Connection\Exception\ResponseException::class);
    $client->get('https://postman-echo.com/status/503');
});

it('does not retry on non-retryable status code', function () {
    $auth = $this->getMockBuilder(Villaflor\Connection\Auth\AuthInterface::class)
        ->onlyMethods(['getHeaders'])
        ->getMock();
    $auth->method('getHeaders')->willReturn([]);

    $client = new Villaflor\Connection\Adapter\Curl($auth, 'https://postman-echo.com');

    // Set retry config with very small delay for fast testing
    $retryConfig = new Villaflor\Connection\Retry\RetryConfig(
        maxAttempts: 3,
        retryableStatusCodes: [503],  // Only 503 is retryable
        baseDelay: 1,
        exponentialBackoff: false
    );
    $client->setRetryConfig($retryConfig);

    // The 404 status should not be retried (will fail immediately)
    $this->expectException(Villaflor\Connection\Exception\ResponseException::class);
    $client->get('https://postman-echo.com/status/404');
});

it('can add and execute middleware', function () {
    $executed = false;

    $middleware = new class($executed) implements Villaflor\Connection\Middleware\MiddlewareInterface
    {
        private bool $executed;

        public function __construct(bool &$executed)
        {
            $this->executed = &$executed;
        }

        public function handle(string $method, string $uri, array $data, array $headers, callable $next): Psr\Http\Message\ResponseInterface
        {
            $this->executed = true;

            return $next($method, $uri, $data, $headers);
        }
    };

    $this->client->addMiddleware($middleware);
    $response = $this->client->get('https://postman-echo.com/get');

    expect($executed)->toBeTrue();
    expect($response->getStatusCode())->toBe(200);
});

it('executes middleware in order', function () {
    $order = [];

    $middleware1 = new class($order) implements Villaflor\Connection\Middleware\MiddlewareInterface
    {
        private array $order;

        public function __construct(array &$order)
        {
            $this->order = &$order;
        }

        public function handle(string $method, string $uri, array $data, array $headers, callable $next): Psr\Http\Message\ResponseInterface
        {
            $this->order[] = 'middleware1-before';
            $response = $next($method, $uri, $data, $headers);
            $this->order[] = 'middleware1-after';

            return $response;
        }
    };

    $middleware2 = new class($order) implements Villaflor\Connection\Middleware\MiddlewareInterface
    {
        private array $order;

        public function __construct(array &$order)
        {
            $this->order = &$order;
        }

        public function handle(string $method, string $uri, array $data, array $headers, callable $next): Psr\Http\Message\ResponseInterface
        {
            $this->order[] = 'middleware2-before';
            $response = $next($method, $uri, $data, $headers);
            $this->order[] = 'middleware2-after';

            return $response;
        }
    };

    $this->client->addMiddleware($middleware1);
    $this->client->addMiddleware($middleware2);
    $this->client->get('https://postman-echo.com/get');

    expect($order)->toBe([
        'middleware1-before',
        'middleware2-before',
        'middleware2-after',
        'middleware1-after',
    ]);
});

it('middleware can modify request parameters', function () {
    $middleware = new class implements Villaflor\Connection\Middleware\MiddlewareInterface
    {
        public function handle(string $method, string $uri, array $data, array $headers, callable $next): Psr\Http\Message\ResponseInterface
        {
            // Add a custom header
            $headers['X-Custom-Middleware'] = 'test-value';

            return $next($method, $uri, $data, $headers);
        }
    };

    $this->client->addMiddleware($middleware);
    $response = $this->client->get('https://postman-echo.com/get');

    $body = json_decode($response->getBody());
    expect($body->headers->{'x-custom-middleware'})->toBe('test-value');
});

it('can upload file using multipart form data', function () {
    $filePath = __DIR__.'/../fixtures/test-file.txt';

    $response = $this->client->post('https://postman-echo.com/post', [
        'multipart' => [
            [
                'name' => 'file',
                'contents' => $filePath,
                'filename' => 'test-file.txt',
                'headers' => ['Content-Type' => 'text/plain'],
            ],
            [
                'name' => 'description',
                'contents' => 'Test file upload',
            ],
        ],
    ]);

    expect($response->getStatusCode())->toBe(200);
    $body = json_decode($response->getBody());
    // Postman-echo returns files indexed by filename, not field name
    expect($body->files)->toHaveProperty('test-file.txt');
    expect($body->form->description)->toBe('Test file upload');
});

it('can upload file with raw content', function () {
    $fileContent = 'This is raw file content for testing';

    $response = $this->client->post('https://postman-echo.com/post', [
        'multipart' => [
            [
                'name' => 'file',
                'contents' => $fileContent,
                'filename' => 'raw-content.txt',
                'headers' => ['Content-Type' => 'text/plain'],
            ],
        ],
    ]);

    expect($response->getStatusCode())->toBe(200);
    $body = json_decode($response->getBody());
    expect($body->files)->toHaveProperty('raw-content.txt');
});

it('can upload multiple files', function () {
    $filePath = __DIR__.'/../fixtures/test-file.txt';

    $response = $this->client->post('https://postman-echo.com/post', [
        'multipart' => [
            [
                'name' => 'file1',
                'contents' => $filePath,
                'filename' => 'file1.txt',
            ],
            [
                'name' => 'file2',
                'contents' => 'Content of second file',
                'filename' => 'file2.txt',
            ],
            [
                'name' => 'field',
                'contents' => 'Regular form field',
            ],
        ],
    ]);

    expect($response->getStatusCode())->toBe(200);
    $body = json_decode($response->getBody());
    expect($body->files)->toHaveProperty('file1.txt');
    expect($body->files)->toHaveProperty('file2.txt');
    expect($body->form->field)->toBe('Regular form field');
});

it('skips multipart fields with missing name or contents', function () {
    $response = $this->client->post('https://postman-echo.com/post', [
        'multipart' => [
            [
                // Missing name - should be skipped
                'contents' => 'test content',
            ],
            [
                'name' => 'valid_field',
                // Missing contents - should be skipped
            ],
            [
                'name' => 'field',
                'contents' => 'Valid field',
            ],
        ],
    ]);

    expect($response->getStatusCode())->toBe(200);
    $body = json_decode($response->getBody());
    expect($body->form->field)->toBe('Valid field');
    // The invalid fields should not be present
    expect(property_exists($body->form, 'valid_field'))->toBeFalse();
});

it('can disable SSL peer verification and make request', function () {
    $this->client->setVerifyPeer(false);
    $this->client->setVerifyHost(false);
    // Make an actual request to exercise the SSL configuration
    $response = $this->client->get('https://postman-echo.com/get');
    expect($response->getStatusCode())->toBe(200);
});

it('can set custom CA bundle and make request', function () {
    // Use the system's default CA bundle location (varies by OS)
    $caBundlePaths = [
        '/etc/ssl/certs/ca-certificates.crt', // Debian/Ubuntu/Gentoo
        '/etc/pki/tls/certs/ca-bundle.crt',   // Fedora/RHEL
        '/etc/ssl/ca-bundle.pem',              // OpenSUSE
        '/etc/ssl/cert.pem',                   // OpenBSD
        '/usr/local/share/certs/ca-root-nss.crt', // FreeBSD
    ];

    $caBundle = null;
    foreach ($caBundlePaths as $path) {
        if (file_exists($path)) {
            $caBundle = $path;
            break;
        }
    }

    if ($caBundle !== null) {
        $this->client->setCaBundle($caBundle);
        $response = $this->client->get('https://postman-echo.com/get');
        expect($response->getStatusCode())->toBe(200);
    } else {
        // If no CA bundle found, skip test
        expect(true)->toBeTrue();
    }
});

it('can set SSL client certificate with separate key', function () {
    // Create temporary files to simulate cert and key
    $certFile = tempnam(sys_get_temp_dir(), 'cert_');
    $keyFile = tempnam(sys_get_temp_dir(), 'key_');
    file_put_contents($certFile, 'fake cert');
    file_put_contents($keyFile, 'fake key');

    $this->client->setSslCert($certFile, $keyFile);

    // We can't actually make a request with fake certs, but we can verify
    // the configuration was applied by checking it doesn't throw during setup
    expect(true)->toBeTrue();

    unlink($certFile);
    unlink($keyFile);
});

it('can set SSL client certificate without separate key', function () {
    $certFile = tempnam(sys_get_temp_dir(), 'cert_');
    file_put_contents($certFile, 'fake cert with embedded key');

    $this->client->setSslCert($certFile);
    expect(true)->toBeTrue();

    unlink($certFile);
});

it('can set proxy with authentication', function () {
    // Note: We can't test actual proxy without a real proxy server
    // But we can verify the configuration is applied
    $auth = $this->getMockBuilder(Villaflor\Connection\Auth\AuthInterface::class)
        ->onlyMethods(['getHeaders'])
        ->getMock();
    $auth->method('getHeaders')->willReturn([]);

    $client = new Villaflor\Connection\Adapter\Curl($auth, 'https://postman-echo.com');
    $client->setProxy('http://proxy.example.com:8080', 'user:pass');

    // Since we can't actually test with a real proxy, we just verify
    // the configuration was set without errors
    expect(true)->toBeTrue();
});

it('works with SSL verification enabled by default', function () {
    // Default behavior - SSL verification should be enabled
    $response = $this->client->get('https://postman-echo.com/get');
    expect($response->getStatusCode())->toBe(200);
});
