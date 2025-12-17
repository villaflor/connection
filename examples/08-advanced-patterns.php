<?php

require_once __DIR__.'/../vendor/autoload.php';

use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Villaflor\Connection\Adapter\Curl;
use Villaflor\Connection\Auth\APIToken;
use Villaflor\Connection\Auth\None;
use Villaflor\Connection\ConnectionBuilder;
use Villaflor\Connection\Events\EventDispatcher;
use Villaflor\Connection\Events\RequestFailedEvent;
use Villaflor\Connection\Events\RequestSendingEvent;
use Villaflor\Connection\Events\ResponseReceivedEvent;
use Villaflor\Connection\Middleware\EventMiddleware;
use Villaflor\Connection\Middleware\LoggingMiddleware;
use Villaflor\Connection\Middleware\MiddlewareInterface;
use Villaflor\Connection\Middleware\RateLimitMiddleware;
use Villaflor\Connection\RateLimit\RateLimiter;
use Villaflor\Connection\Retry\RetryConfig;

// Simple logger for examples
class ConsoleLogger implements LoggerInterface
{
    public function log($level, $message, array $context = []): void
    {
        echo "[$level] $message\n";
    }

    public function emergency($message, array $context = []): void
    {
        $this->log('emergency', $message, $context);
    }

    public function alert($message, array $context = []): void
    {
        $this->log('alert', $message, $context);
    }

    public function critical($message, array $context = []): void
    {
        $this->log('critical', $message, $context);
    }

    public function error($message, array $context = []): void
    {
        $this->log('error', $message, $context);
    }

    public function warning($message, array $context = []): void
    {
        $this->log('warning', $message, $context);
    }

    public function notice($message, array $context = []): void
    {
        $this->log('notice', $message, $context);
    }

    public function info($message, array $context = []): void
    {
        $this->log('info', $message, $context);
    }

    public function debug($message, array $context = []): void
    {
        $this->log('debug', $message, $context);
    }
}

// Example 1: Resilient API Client with Retry + Rate Limiting + Logging
echo "=== Example 1: Resilient API Client ===\n";

$client = new Curl(
    new APIToken('demo-token'),
    'https://httpbin.org'
);

// Configure retry for transient failures
$retryConfig = new RetryConfig(
    maxAttempts: 3,
    retryableStatusCodes: [429, 500, 502, 503, 504],
    exponentialBackoff: true,
    baseDelay: 1000,
    maxDelay: 10000
);
$client->setRetryConfig($retryConfig);

// Add rate limiting (5 requests per second)
$rateLimiter = new RateLimiter(maxRequests: 5, perSeconds: 1);
$client->addMiddleware(new RateLimitMiddleware($rateLimiter));

// Add logging
$logger = new ConsoleLogger;
$client->addMiddleware(new LoggingMiddleware(
    $logger,
    requestLevel: LogLevel::INFO,
    responseLevel: LogLevel::INFO,
    errorLevel: LogLevel::ERROR
));

echo "Client configured with retry, rate limiting, and logging\n";
echo "Making request...\n\n";

try {
    $response = $client->get('/get');
    echo 'Success! Status: '.$response->getStatusCode()."\n\n";
} catch (Exception $e) {
    echo 'Request failed: '.$e->getMessage()."\n\n";
}

// Example 2: Production-Ready Client with Full Observability
echo "=== Example 2: Production Client with Events ===\n";

$dispatcher = new EventDispatcher;
$logger = new ConsoleLogger;
$metrics = [
    'requests' => 0,
    'errors' => 0,
    'total_duration' => 0,
];

// Track all requests
$dispatcher->listen('request.sending', function (RequestSendingEvent $event) use (&$metrics) {
    $metrics['requests']++;
});

// Track response times
$dispatcher->listen('response.received', function (ResponseReceivedEvent $event) use (&$metrics) {
    $metrics['total_duration'] += $event->duration;
    $duration = round($event->duration * 1000, 2);

    if ($duration > 1000) {
        echo "[ALERT] Slow request detected: {$event->uri} took {$duration}ms\n";
    }
});

// Track errors
$dispatcher->listen('request.failed', function (RequestFailedEvent $event) use (&$metrics) {
    $metrics['errors']++;
});

$client = ConnectionBuilder::create()
    ->withBaseUri('https://httpbin.org')
    ->withBearerToken('demo-token')
    ->withTimeout(30)
    ->build();

// Add event tracking
$client->addMiddleware(new EventMiddleware($dispatcher));

// Add retry logic
$client->setRetryConfig(new RetryConfig(
    maxAttempts: 3,
    retryableStatusCodes: [500, 502, 503, 504],
    exponentialBackoff: true
));

// Add logging
$client->addMiddleware(new LoggingMiddleware($logger, LogLevel::INFO, LogLevel::INFO, LogLevel::ERROR));

echo "Making multiple requests...\n";

$client->get('/get');
$client->post('/post', ['data' => 'test']);
$client->get('/delay/1'); // Slow request

echo "\nMetrics Summary:\n";
echo "- Total Requests: {$metrics['requests']}\n";
echo "- Errors: {$metrics['errors']}\n";
echo '- Average Duration: '.round(($metrics['total_duration'] / max($metrics['requests'], 1)) * 1000, 2)."ms\n\n";

// Example 3: Circuit Breaker Pattern with Events
echo "=== Example 3: Circuit Breaker Pattern ===\n";

class CircuitBreakerMiddleware implements MiddlewareInterface
{
    private int $failures = 0;

    private bool $isOpen = false;

    private ?float $openedAt = null;

    public function __construct(
        private int $failureThreshold = 5,
        private int $resetTimeout = 60  // seconds
    ) {}

    public function handle(
        string $method,
        string $uri,
        array $data,
        array $headers,
        callable $next
    ): \Psr\Http\Message\ResponseInterface {
        // Check if circuit should be reset
        if ($this->isOpen && $this->openedAt && (microtime(true) - $this->openedAt) > $this->resetTimeout) {
            echo "[CIRCUIT BREAKER] Attempting to reset circuit\n";
            $this->isOpen = false;
            $this->failures = 0;
        }

        // If circuit is open, fail fast
        if ($this->isOpen) {
            throw new \Exception('Circuit breaker is OPEN - failing fast to protect downstream service');
        }

        try {
            $response = $next($method, $uri, $data, $headers);

            // Success - reset failure count
            if ($this->failures > 0) {
                echo "[CIRCUIT BREAKER] Request succeeded, resetting failure count\n";
                $this->failures = 0;
            }

            return $response;
        } catch (\Exception $e) {
            $this->failures++;
            echo "[CIRCUIT BREAKER] Failure #{$this->failures}\n";

            // Open circuit if threshold exceeded
            if ($this->failures >= $this->failureThreshold) {
                $this->isOpen = true;
                $this->openedAt = microtime(true);
                echo "[CIRCUIT BREAKER] OPEN - threshold exceeded ({$this->failureThreshold} failures)\n";
            }

            throw $e;
        }
    }
}

$client = new Curl(new None, 'https://httpbin.org');
$client->addMiddleware(new CircuitBreakerMiddleware(failureThreshold: 3, resetTimeout: 5));

echo "Circuit breaker configured (threshold: 3 failures)\n";
echo "Testing with failing endpoints...\n\n";

for ($i = 1; $i <= 5; $i++) {
    try {
        echo "Request #{$i}: ";
        $client->get('/status/500'); // Will fail
        echo "Success\n";
    } catch (Exception $e) {
        echo 'Failed - '.$e->getMessage()."\n";
    }
}

echo "\n";

// Example 4: Request Caching Middleware
echo "=== Example 4: Request Caching ===\n";

class CachingMiddleware implements MiddlewareInterface
{
    private array $cache = [];

    public function __construct(private int $ttl = 60) {}

    private function getCacheKey(string $method, string $uri, array $data): string
    {
        return md5($method.$uri.json_encode($data));
    }

    public function handle(
        string $method,
        string $uri,
        array $data,
        array $headers,
        callable $next
    ): \Psr\Http\Message\ResponseInterface {
        // Only cache GET requests
        if ($method !== 'GET') {
            return $next($method, $uri, $data, $headers);
        }

        $cacheKey = $this->getCacheKey($method, $uri, $data);

        // Check cache
        if (isset($this->cache[$cacheKey])) {
            $cached = $this->cache[$cacheKey];
            if (time() - $cached['time'] < $this->ttl) {
                echo "[CACHE] HIT for {$uri}\n";

                return $cached['response'];
            }
            echo "[CACHE] EXPIRED for {$uri}\n";
        }

        echo "[CACHE] MISS for {$uri}\n";
        $response = $next($method, $uri, $data, $headers);

        // Store in cache
        $this->cache[$cacheKey] = [
            'response' => $response,
            'time' => time(),
        ];

        return $response;
    }
}

$client = new Curl(new None, 'https://httpbin.org');
$client->addMiddleware(new CachingMiddleware(ttl: 60));

echo "Making first request (cache miss)...\n";
$client->get('/get');

echo "\nMaking second request (cache hit)...\n";
$client->get('/get');

echo "\nMaking different request (cache miss)...\n";
$client->get('/headers');

echo "\n";

// Example 5: Request/Response Transformation Middleware
echo "=== Example 5: Request/Response Transformation ===\n";

class TransformationMiddleware implements MiddlewareInterface
{
    public function handle(
        string $method,
        string $uri,
        array $data,
        array $headers,
        callable $next
    ): \Psr\Http\Message\ResponseInterface {
        // Add custom headers to all requests
        $headers['X-App-Version'] = '1.0.0';
        $headers['X-Request-ID'] = uniqid('req_', true);

        // Transform request data (e.g., snake_case to camelCase)
        if (! empty($data)) {
            echo "[TRANSFORM] Adding metadata to request\n";
            $data['_metadata'] = [
                'client' => 'php-connection',
                'timestamp' => time(),
            ];
        }

        $response = $next($method, $uri, $data, $headers);

        // You could transform response here as well
        echo "[TRANSFORM] Response received with status {$response->getStatusCode()}\n";

        return $response;
    }
}

$client = new Curl(new None, 'https://httpbin.org');
$client->addMiddleware(new TransformationMiddleware);

$response = $client->post('/post', ['user_name' => 'John']);
echo "\n";

// Example 6: Complete Production Setup
echo "=== Example 6: Complete Production Setup ===\n";

// This combines everything for a production-ready client
$client = ConnectionBuilder::create()
    ->withBaseUri('https://api.production.com')
    ->withBearerToken('prod-api-token')
    ->withTimeout(30)
    ->withConnectTimeout(10)
    ->build();

// 1. Rate limiting (100 requests per minute)
$rateLimiter = new RateLimiter(maxRequests: 100, perSeconds: 60);
$client->addMiddleware(new RateLimitMiddleware($rateLimiter, 'production-api'));

// 2. Circuit breaker for resilience
$client->addMiddleware(new CircuitBreakerMiddleware(failureThreshold: 5, resetTimeout: 60));

// 3. Request caching for GET requests
$client->addMiddleware(new CachingMiddleware(ttl: 300)); // 5 minutes

// 4. Request transformation
$client->addMiddleware(new TransformationMiddleware);

// 5. Events for observability
$dispatcher = new EventDispatcher;
$dispatcher->listen('request.sending', function (RequestSendingEvent $event) {
    // Send to monitoring/APM
    echo "[MONITOR] Request: {$event->method} {$event->uri}\n";
});
$dispatcher->listen('response.received', function (ResponseReceivedEvent $event) {
    // Track metrics
    echo "[MONITOR] Response: {$event->response->getStatusCode()} in ".round($event->duration * 1000)."ms\n";
});
$dispatcher->listen('request.failed', function (RequestFailedEvent $event) {
    // Alert on errors
    echo "[ALERT] Request failed: {$event->exception->getMessage()}\n";
});
$client->addMiddleware(new EventMiddleware($dispatcher));

// 6. Logging
$logger = new ConsoleLogger;
$client->addMiddleware(new LoggingMiddleware($logger, LogLevel::INFO, LogLevel::INFO, LogLevel::ERROR));

// 7. Retry logic
$retryConfig = new RetryConfig(
    maxAttempts: 3,
    retryableStatusCodes: [408, 429, 500, 502, 503, 504],
    exponentialBackoff: true,
    baseDelay: 1000,
    maxDelay: 30000
);
$client->setRetryConfig($retryConfig);

// 8. SSL Configuration
$client->setVerifyPeer(true);
$client->setVerifyHost(true);

echo "Production client configured with:\n";
echo "- Rate limiting: 100 req/min\n";
echo "- Circuit breaker: 5 failure threshold\n";
echo "- Caching: 5 minute TTL for GET requests\n";
echo "- Request transformation and metadata\n";
echo "- Event-based monitoring\n";
echo "- Structured logging\n";
echo "- Retry with exponential backoff\n";
echo "- SSL verification enabled\n";
echo "\nThis client is production-ready!\n\n";

echo "Advanced patterns examples complete!\n";
