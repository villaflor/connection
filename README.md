# Connection - HTTP Client for PHP

[![Latest Version on Packagist](https://img.shields.io/packagist/v/villaflor/connection.svg?style=flat-square)](https://packagist.org/packages/villaflor/connection)
[![PHP Version Supported](https://img.shields.io/packagist/php-v/villaflor/connection?style=flat-square)](https://packagist.org/packages/villaflor/connection)
[![License](https://img.shields.io/github/license/villaflor/connection.svg?style=flat-square)](https://github.com/villaflor/connection/blob/main/LICENSE)
[![Total Downloads](https://img.shields.io/packagist/dt/villaflor/connection.svg?style=flat-square)](https://packagist.org/packages/villaflor/connection)
[![Tests](https://github.com/villaflor/connection/actions/workflows/run-tests.yml/badge.svg?branch=main)](https://github.com/villaflor/connection/actions/workflows/run-tests.yml)
[![100% Coverage](https://img.shields.io/badge/coverage-100%25-brightgreen.svg?style=flat-square)]()

A lightweight, PSR-7 compliant HTTP client for PHP 8.3+ with no dependencies. Send requests to any API endpoint with ease using a clean, SOLID architecture.

## Features

✅ **Zero Dependencies** - Native cURL implementation, no Guzzle required
✅ **100% Test Coverage** - Fully tested and reliable
✅ **PSR-7 Compliant** - Standard HTTP message interfaces
✅ **Multiple Auth Methods** - API Token, API Key, Google Service Account, Custom Headers
✅ **SOLID Architecture** - Clean, maintainable, extensible code
✅ **Type Safe** - Full PHP 8.3+ type hints
✅ **Configurable** - Timeouts, SSL, redirects, and more

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
