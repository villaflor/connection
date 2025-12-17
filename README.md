# Connection - HTTP Client for PHP

[![Latest Version on Packagist](https://img.shields.io/packagist/v/villaflor/connection.svg?style=flat-square)](https://packagist.org/packages/villaflor/connection)
[![PHP Version Supported](https://img.shields.io/packagist/php-v/villaflor/connection?style=flat-square)](https://packagist.org/packages/villaflor/connection)
[![License](https://img.shields.io/github/license/villaflor/connection.svg?style=flat-square)](https://github.com/villaflor/connection/blob/main/LICENSE)
[![Total Downloads](https://img.shields.io/packagist/dt/villaflor/connection.svg?style=flat-square)](https://packagist.org/packages/villaflor/connection)
[![Tests](https://github.com/villaflor/connection/actions/workflows/run-tests.yml/badge.svg?branch=main)](https://github.com/villaflor/connection/actions/workflows/run-tests.yml)
[![100% Coverage](https://img.shields.io/badge/coverage-100%25-brightgreen.svg?style=flat-square)]()

A lightweight, PSR-7 compliant HTTP client for PHP 8.3+ with no dependencies. Send requests to any API endpoint with ease using a clean, SOLID architecture.

## Features

### Core Features
✅ **Zero Dependencies** - Native cURL implementation, no Guzzle required
✅ **100% Test Coverage** - Fully tested and reliable
✅ **PSR-7 Compliant** - Standard HTTP message interfaces
✅ **Multiple Auth Methods** - API Token, API Key, Google Service Account, Custom Headers
✅ **SOLID Architecture** - Clean, maintainable, extensible code
✅ **Type Safe** - Full PHP 8.3+ type hints

### Advanced Features
✅ **Middleware Pipeline** - Extensible request/response processing
✅ **Retry Logic** - Automatic retry with exponential backoff
✅ **Rate Limiting** - Token bucket algorithm for API rate limits
✅ **Response Caching** - HTTP-aware caching with TTL support
✅ **Cookie Management** - Automatic cookie jar with session persistence
✅ **Event System** - Observable HTTP lifecycle events
✅ **File Uploads** - Multipart form data support
✅ **Proxy & SSL** - Full proxy and SSL/TLS configuration
✅ **PSR-3 Logging** - Request/response logging support

## Installation

```bash
composer require villaflor/connection
```

## Requirements

- PHP 8.3 or higher
- ext-curl
- ext-json
- ext-openssl

## Quick Start

```php
use Villaflor\Connection\Adapter\Curl;
use Villaflor\Connection\Auth\APIToken;

// Create a client with Bearer token authentication
$auth = new APIToken('your-api-token-here');
$client = new Curl($auth, 'https://api.example.com');

// Make a GET request
$response = $client->get('/users');
$data = json_decode($response->getBody());

// Make a POST request
$response = $client->post('/users', [
    'name' => 'John Doe',
    'email' => 'john@example.com',
]);
```

## Authentication Methods

### API Token (Bearer)

```php
use Villaflor\Connection\Auth\APIToken;

$auth = new APIToken('your-token');
// Sends: Authorization: Bearer your-token
```

### API Key

```php
use Villaflor\Connection\Auth\APIKey;

$auth = new APIKey('your-api-key');
// Sends: X-API-Key: your-api-key
```

### Custom Headers

```php
use Villaflor\Connection\Auth\CustomHeaders;

$auth = new CustomHeaders([
    'X-Custom-Auth' => 'custom-value',
    'X-Client-ID' => 'client-123',
]);
```

### User Service Key

```php
use Villaflor\Connection\Auth\UserServiceKey;

$auth = new UserServiceKey('user-id', 'service-key');
// Sends: X-User-Id: user-id, X-Service-Key: service-key
```

### Google Service Account

```php
use Villaflor\Connection\Auth\GoogleServiceAccount;

$auth = new GoogleServiceAccount(
    '/path/to/service-account.json',
    'https://www.googleapis.com/auth/cloud-platform'
);
// Generates and sends JWT token
```

### No Authentication

```php
use Villaflor\Connection\Auth\None;

$auth = new None();
```

## HTTP Methods

### GET Request

```php
// Simple GET
$response = $client->get('/users');

// GET with query parameters
$response = $client->get('/users', ['page' => 1, 'limit' => 10]);

// GET with custom headers
$response = $client->get('/users', [], ['X-Custom' => 'value']);
```

### POST Request

```php
// POST with JSON body
$response = $client->post('/users', [
    'name' => 'John Doe',
    'email' => 'john@example.com',
]);

// POST with form data
$response = $client->post('/users', [
    'form_params' => [
        'name' => 'John Doe',
        'email' => 'john@example.com',
    ],
]);
```

### PUT Request

```php
$response = $client->put('/users/123', [
    'name' => 'Jane Doe',
    'email' => 'jane@example.com',
]);
```

### PATCH Request

```php
$response = $client->patch('/users/123', [
    'email' => 'newemail@example.com',
]);
```

### DELETE Request

```php
$response = $client->delete('/users/123');

// DELETE with body (if API requires it)
$response = $client->delete('/users/123', [
    'reason' => 'User requested deletion',
]);
```

## Configuration

### Timeouts

```php
$client = new Curl($auth, 'https://api.example.com');

// Set request timeout (default: 30 seconds)
$client->setTimeout(60);

// Set connection timeout (default: 10 seconds)
$client->setConnectTimeout(5);
```

### Using Relative URLs

```php
// Base URI is set in constructor
$client = new Curl($auth, 'https://api.example.com');

// Use relative paths
$response = $client->get('/users'); // https://api.example.com/users
$response = $client->get('posts');  // https://api.example.com/posts

// Or use absolute URLs (ignores base URI)
$response = $client->get('https://other-api.com/data');
```

## Working with Responses

All responses implement PSR-7 `ResponseInterface`:

```php
$response = $client->get('/users');

// Get status code
$statusCode = $response->getStatusCode(); // 200

// Get reason phrase
$reason = $response->getReasonPhrase(); // "OK"

// Get headers
$headers = $response->getHeaders();
$contentType = $response->getHeaderLine('Content-Type');

// Get body
$body = (string) $response->getBody();
$data = json_decode($response->getBody());

// PSR-7 methods
$response = $response->withHeader('X-Custom', 'value'); // Immutable
$newResponse = $response->withStatus(404, 'Not Found');
```

## Error Handling

```php
use Villaflor\Connection\Exception\ResponseException;
use Villaflor\Connection\Exception\JSONException;

try {
    $response = $client->get('/users/999');
} catch (ResponseException $e) {
    // HTTP 4xx or 5xx error
    echo "Error: " . $e->getMessage();
    echo "Status Code: " . $e->getCode();

    // Access previous exception if available
    if ($e->getPrevious() instanceof JSONException) {
        echo "JSON parsing failed";
    }
}
```

### Exception Types

- `ResponseException` - Thrown for HTTP 4xx and 5xx errors, or cURL errors
- `JSONException` - Thrown when JSON response cannot be decoded
- `InvalidArgumentException` - Thrown for invalid method names or parameters

### Error Response Formats

The library automatically parses various JSON error formats:

```php
// Format 1: errors array
{"errors": [{"code": 1003, "message": "Invalid input"}]}

// Format 2: message field
{"message": "Resource not found"}

// Format 3: error field (string)
{"error": "Authentication failed"}

// Format 4: error field (object)
{"error": {"message": "Rate limit exceeded"}}
```

## Advanced Usage

### Custom Request Method

```php
$response = $client->request('GET', '/users', ['page' => 1], ['X-Custom' => 'value']);
```

### Working with Different Content Types

```php
// JSON (default)
$response = $client->post('/api/data', ['key' => 'value']);
// Sends: Content-Type: application/json

// Form data
$response = $client->post('/api/form', [
    'form_params' => ['key' => 'value']
]);
// Sends: Content-Type: application/x-www-form-urlencoded
```

### Query Parameters with Existing Query String

```php
// Automatically handles existing query parameters
$response = $client->get('/search?q=test', ['page' => 2]);
// Requests: /search?q=test&page=2
```

## Fluent API with ConnectionBuilder

Build clients with a fluent, chainable API:

```php
use Villaflor\Connection\ConnectionBuilder;

$client = ConnectionBuilder::create()
    ->withBaseUri('https://api.example.com')
    ->withBearerToken('your-token')
    ->withTimeout(60)
    ->withConnectTimeout(10)
    ->build();

// Make requests
$response = $client->get('/users');
```

## Advanced Features

### Retry Logic with Exponential Backoff

Automatically retry failed requests with configurable backoff:

```php
use Villaflor\Connection\Retry\RetryConfig;

$client = new Curl($auth, 'https://api.example.com');

// Configure retry behavior
$retryConfig = new RetryConfig(
    maxAttempts: 3,                                      // Retry up to 3 times
    retryableStatusCodes: [408, 429, 500, 502, 503, 504], // Which status codes to retry
    exponentialBackoff: true,                            // Use exponential backoff
    baseDelay: 1000,                                     // Start with 1 second delay
    maxDelay: 30000                                      // Maximum 30 seconds delay
);

$client->setRetryConfig($retryConfig);

// Requests will automatically retry on failures
$response = $client->get('/unstable-endpoint');
```

Retry delays: 1s → 2s → 4s → 8s → ...

### Middleware Pipeline

Extend functionality with custom middleware:

```php
use Villaflor\Connection\Middleware\MiddlewareInterface;
use Psr\Http\Message\ResponseInterface;

class CustomMiddleware implements MiddlewareInterface
{
    public function handle(
        string $method,
        string $uri,
        array $data,
        array $headers,
        callable $next
    ): ResponseInterface {
        // Before request
        $headers['X-Custom-Header'] = 'value';

        // Execute request
        $response = $next($method, $uri, $data, $headers);

        // After response
        return $response;
    }
}

$client->addMiddleware(new CustomMiddleware);
```

### Rate Limiting

Prevent exceeding API rate limits:

```php
use Villaflor\Connection\RateLimit\RateLimiter;
use Villaflor\Connection\Middleware\RateLimitMiddleware;

$client = new Curl($auth, 'https://api.example.com');

// Limit to 100 requests per 60 seconds
$limiter = new RateLimiter(maxRequests: 100, perSeconds: 60);
$client->addMiddleware(new RateLimitMiddleware($limiter));

// Requests will automatically throttle
for ($i = 0; $i < 200; $i++) {
    $client->get('/data'); // Will throttle after 100 requests
}
```

### Response Caching

Cache responses to improve performance:

```php
use Villaflor\Connection\Cache\ArrayCache;
use Villaflor\Connection\Middleware\CachingMiddleware;

$client = new Curl($auth, 'https://api.example.com');

$cache = new ArrayCache;
$cachingMiddleware = new CachingMiddleware(
    cache: $cache,
    defaultTtl: 300,  // 5 minutes
    cacheableMethods: ['GET']
);

$client->addMiddleware($cachingMiddleware);

// First request hits the API
$response1 = $client->get('/data'); // ~200ms

// Second request uses cache
$response2 = $client->get('/data'); // <1ms
```

### Cookie Management

Automatically handle cookies and sessions:

```php
use Villaflor\Connection\Cookie\CookieJar;
use Villaflor\Connection\Middleware\CookieMiddleware;

$client = new Curl($auth, 'https://api.example.com');

$jar = new CookieJar;
$client->addMiddleware(new CookieMiddleware($jar));

// Login request sets cookies
$client->post('/login', ['username' => 'user', 'password' => 'pass']);

// Subsequent requests automatically include cookies
$client->get('/dashboard'); // Cookie sent automatically
$client->get('/profile');   // Cookie sent automatically
```

### Event System

Monitor and observe HTTP lifecycle:

```php
use Villaflor\Connection\Events\EventDispatcher;
use Villaflor\Connection\Middleware\EventMiddleware;

$dispatcher = new EventDispatcher;

// Listen for events
$dispatcher->listen('request.sending', function ($event) {
    echo "Sending: {$event->method} {$event->uri}\n";
});

$dispatcher->listen('response.received', function ($event) {
    echo "Received: {$event->response->getStatusCode()} in {$event->duration}s\n";
});

$dispatcher->listen('request.failed', function ($event) {
    echo "Failed: {$event->exception->getMessage()}\n";
});

$client->addMiddleware(new EventMiddleware($dispatcher));
```

### Request/Response Logging

PSR-3 compatible logging:

```php
use Villaflor\Connection\Middleware\LoggingMiddleware;
use Psr\Log\LogLevel;

$client = new Curl($auth, 'https://api.example.com');

$client->addMiddleware(new LoggingMiddleware(
    logger: $logger,           // Any PSR-3 logger
    requestLevel: LogLevel::INFO,
    responseLevel: LogLevel::INFO,
    errorLevel: LogLevel::ERROR
));

// All requests/responses are logged
$client->get('/data');
```

### File Uploads

Upload files with multipart/form-data:

```php
// Upload from file path
$client->post('/upload', [
    'description' => 'My file',
    'file' => '/path/to/file.pdf',
]);

// Upload raw content
$client->post('/upload', [
    'file' => [
        'name' => 'document.pdf',
        'contents' => $fileContent,
        'mime_type' => 'application/pdf',
    ],
]);

// Multiple files
$client->post('/upload', [
    'file1' => '/path/to/file1.pdf',
    'file2' => '/path/to/file2.jpg',
]);
```

### Proxy Configuration

Route requests through a proxy:

```php
$client = new Curl($auth, 'https://api.example.com');

// Basic proxy
$client->setProxy('proxy.example.com:8080');

// Proxy with authentication
$client->setProxy('proxy.example.com:8080', 'username:password');

// With ConnectionBuilder
$client = ConnectionBuilder::create()
    ->withBaseUri('https://api.example.com')
    ->withProxy('proxy.example.com:8080', 'user:pass')
    ->build();
```

### SSL/TLS Configuration

Configure SSL verification and certificates:

```php
$client = new Curl($auth, 'https://api.example.com');

// Enable/disable SSL verification
$client->setVerifyPeer(true);  // Verify SSL certificate
$client->setVerifyHost(true);  // Verify hostname

// Custom CA bundle
$client->setCaBundle('/path/to/ca-bundle.crt');

// Client certificates (mutual TLS)
$client->setSslCert('/path/to/cert.pem', '/path/to/key.pem');

// With ConnectionBuilder
$client = ConnectionBuilder::create()
    ->withBaseUri('https://api.example.com')
    ->withVerifyPeer(true)
    ->withCaBundle('/path/to/ca-bundle.crt')
    ->build();
```

## Examples

Comprehensive examples are available in the [`examples/`](examples/) directory:

1. **01-basic-usage.php** - HTTP methods and basic operations
2. **02-authentication.php** - All authentication strategies
3. **03-retry-logic.php** - Retry configuration patterns
4. **04-middleware.php** - Middleware system
5. **05-file-uploads.php** - File upload examples
6. **06-events.php** - Event system usage
7. **07-proxy-ssl.php** - Proxy and SSL configuration
8. **08-advanced-patterns.php** - Production patterns
9. **09-caching.php** - Response caching
10. **10-cookies.php** - Cookie management

See [`examples/README.md`](examples/README.md) for a complete guide.

## Migration from Guzzle

If you're migrating from the previous Guzzle-based version:

**Before (v4.x with Guzzle):**
```php
use Villaflor\Connection\Adapter\Guzzle;

$client = new Guzzle($auth, 'https://api.example.com');
```

**After (v5.x with Curl):**
```php
use Villaflor\Connection\Adapter\Curl;

$client = new Curl($auth, 'https://api.example.com');
```

The API is identical - just change the class name! All methods, parameters, and return types remain the same.

## Architecture

This package follows SOLID principles with a clean architecture:

- **Adapter Pattern**: Easily swap HTTP clients (currently Curl, previously Guzzle)
- **Strategy Pattern**: Multiple authentication strategies
- **Dependency Inversion**: Depend on interfaces, not concrete implementations
- **PSR-7 Compliance**: Standard HTTP message interfaces

### Interface Hierarchy

```
AdapterInterface
├── Curl (Native PHP cURL implementation)

AuthInterface
├── APIToken
├── APIKey
├── CustomHeaders
├── UserServiceKey
├── GoogleServiceAccount
└── None

ResponseInterface (PSR-7)
└── Response

StreamInterface (PSR-7)
└── Stream
```

## Testing

```bash
# Run tests
composer test

# Run tests with coverage
composer test-coverage

# Run code formatting
composer format
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for recent changes.

## Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

- [Mark Anthony Villaflor](https://github.com/villaflor)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
