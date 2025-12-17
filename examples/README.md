# Connection Library Examples

This directory contains comprehensive examples demonstrating all features of the Villaflor Connection library.

## Running Examples

All examples can be run directly from the command line:

```bash
php examples/01-basic-usage.php
php examples/02-authentication.php
# ... etc
```

## Example Files

### 01-basic-usage.php
**Basic HTTP operations and convenience methods**

Learn the fundamentals:
- Making GET, POST, PUT, PATCH, DELETE requests
- Using JSON convenience methods (`getJson()`, `postJson()`)
- Using ConnectionBuilder for fluent API
- Adding custom headers to requests
- Basic error handling

**Best for:** Getting started with the library

---

### 02-authentication.php
**All authentication strategies**

Demonstrates every auth method:
- Bearer Token (APIToken)
- API Key authentication (Basic Auth)
- Custom headers
- Google Service Account (OAuth2)
- User Service Key
- No authentication
- Using auth with ConnectionBuilder

**Best for:** Understanding how to authenticate with different APIs

---

### 03-retry-logic.php
**Retry configuration and exponential backoff**

Master retry behavior:
- Default retry configuration
- Custom retry settings (max attempts, status codes)
- Exponential vs linear backoff
- Rate limit specific retries
- Understanding retry delays
- Combining retry with timeouts

**Best for:** Building resilient API clients that handle transient failures

---

### 04-middleware.php
**Middleware system and request/response pipeline**

Learn middleware patterns:
- Creating custom middleware
- Logging middleware with PSR-3 logger
- Rate limiting middleware
- Middleware execution order
- Authentication middleware
- Chaining multiple middleware

**Best for:** Extending client functionality and cross-cutting concerns

---

### 05-file-uploads.php
**File upload and multipart form data**

Complete file upload guide:
- Uploading files from disk
- Uploading raw file content (from memory)
- Multiple file uploads
- Custom MIME types
- Mixed form data and files
- Error handling for uploads

**Best for:** Working with file uploads and multipart/form-data

---

### 06-events.php
**Event system and observability**

Comprehensive event handling:
- Listening to request/response/error events
- Performance monitoring with events
- Error logging and handling
- Request/response inspection
- Multiple listeners per event
- Conditional event handling
- Complete event lifecycle
- Managing listeners

**Best for:** Monitoring, debugging, and observability

---

### 07-proxy-ssl.php
**Proxy and SSL/TLS configuration**

Security and network configuration:
- Basic proxy setup
- Proxy with authentication
- SSL/TLS verification settings
- Custom CA bundles
- Client SSL certificates (mutual TLS)
- Combined proxy + SSL setup
- Enterprise/corporate configurations
- Best practices and security warnings

**Best for:** Enterprise environments, custom SSL requirements

---

### 08-advanced-patterns.php
**Production-ready patterns combining multiple features**

Advanced real-world patterns:
- Resilient API client (retry + rate limit + logging)
- Production client with full observability
- Circuit breaker pattern
- Request caching middleware
- Request/response transformation
- Complete production setup example

**Best for:** Building production-ready, enterprise-grade API clients

---

### 09-caching.php
**Response caching for performance optimization**

Complete caching guide:
- Basic response caching with TTL
- Cache-Control header respect
- Caching successful responses only
- Method-specific caching (GET vs POST)
- Cache key generation
- Cache management and statistics
- Performance benefits demonstration

**Best for:** Optimizing performance by avoiding redundant API calls

---

### 10-cookies.php
**Cookie management and session persistence**

Comprehensive cookie handling:
- Automatic cookie management
- Manual cookie creation
- Cookie attributes (domain, path, secure, httpOnly)
- Parsing Set-Cookie headers
- Cookie expiration handling
- Domain and path matching
- Session persistence across requests
- Cookie jar manipulation

**Best for:** Managing sessions, authentication, and stateful HTTP interactions

---

## Feature Matrix

| Feature | Examples |
|---------|----------|
| HTTP Methods (GET, POST, etc.) | 01, 08 |
| Authentication | 02, 08 |
| Retry Logic | 03, 08 |
| Middleware | 04, 08 |
| File Uploads | 05 |
| Events | 06, 08 |
| Rate Limiting | 04, 08 |
| Proxy | 07, 08 |
| SSL/TLS | 07, 08 |
| Logging | 04, 08 |
| Caching | 08, 09 |
| Cookies | 10 |
| Circuit Breaker | 08 |
| ConnectionBuilder | 01, 02, 07, 08 |

## Learning Path

**Beginner:**
1. Start with `01-basic-usage.php` - Learn the basics
2. Then `02-authentication.php` - Understand auth methods
3. Try `05-file-uploads.php` - Work with files

**Intermediate:**
4. Study `03-retry-logic.php` - Build resilient clients
5. Explore `04-middleware.php` - Extend functionality
6. Review `06-events.php` - Add observability

**Advanced:**
7. Master `07-proxy-ssl.php` - Handle security and networking
8. Apply `08-advanced-patterns.php` - Production patterns

## Common Use Cases

### Simple API Integration
```php
// See: 01-basic-usage.php, 02-authentication.php
$client = ConnectionBuilder::create()
    ->withBaseUri('https://api.example.com')
    ->withBearerToken('your-token')
    ->build();

$data = $client->getJson('/endpoint');
```

### Resilient Production Client
```php
// See: 08-advanced-patterns.php (Example 1)
$client = new Curl(new APIToken('token'), 'https://api.example.com');
$client->setRetryConfig(new RetryConfig(maxAttempts: 3));
$client->addMiddleware(new RateLimitMiddleware(new RateLimiter(100, 60)));
$client->addMiddleware(new LoggingMiddleware($logger));
```

### Enterprise with Proxy + SSL
```php
// See: 07-proxy-ssl.php (Example 10)
$client = ConnectionBuilder::create()
    ->withBaseUri('https://internal-api.company.com')
    ->withProxy('proxy.company.com:3128', 'user:pass')
    ->withCaBundle('/etc/ssl/company-ca.crt')
    ->withVerifyPeer(true)
    ->build();
```

### Observable Client with Events
```php
// See: 06-events.php (Example 2)
$dispatcher = new EventDispatcher;
$dispatcher->listen('response.received', function($event) {
    // Track metrics, log performance, etc.
});

$client = new Curl(new None, 'https://api.example.com');
$client->addMiddleware(new EventMiddleware($dispatcher));
```

## Testing Examples

The examples use public testing APIs:
- **httpbin.org** - HTTP request/response testing
- **postman-echo.com** - File upload and echo testing
- **badssl.com** - SSL/TLS testing

These endpoints are free and require no authentication for basic testing.

## Need Help?

- Check the main README.md in the project root
- Review the PHPDoc in the source code
- Look at the comprehensive tests in `tests/` directory
- All examples include detailed comments explaining each feature

## Contributing

Found an issue or want to add an example? Contributions welcome!
