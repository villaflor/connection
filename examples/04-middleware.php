<?php

require_once __DIR__.'/../vendor/autoload.php';

use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Villaflor\Connection\Adapter\Curl;
use Villaflor\Connection\Auth\None;
use Villaflor\Connection\Middleware\LoggingMiddleware;
use Villaflor\Connection\Middleware\MiddlewareInterface;
use Villaflor\Connection\Middleware\RateLimitMiddleware;
use Villaflor\Connection\RateLimit\RateLimiter;

// Example 1: Custom Middleware - Add timestamp header
echo "=== Example 1: Custom Middleware ===\n";

class TimestampMiddleware implements MiddlewareInterface
{
    public function handle(
        string $method,
        string $uri,
        array $data,
        array $headers,
        callable $next
    ): \Psr\Http\Message\ResponseInterface {
        // Add timestamp header to all requests
        $headers['X-Request-Timestamp'] = (string) time();

        return $next($method, $uri, $data, $headers);
    }
}

$client = new Curl(new None, 'https://httpbin.org');
$client->addMiddleware(new TimestampMiddleware);

$response = $client->get('/headers');
echo 'Response: '.$response->getStatusCode()."\n\n";

// Example 2: Logging Middleware with PSR-3 Logger
echo "=== Example 2: Logging Middleware ===\n";

// Simple console logger for demonstration
class ConsoleLogger implements LoggerInterface
{
    public function log($level, $message, array $context = []): void
    {
        echo "[$level] $message ".json_encode($context)."\n";
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

$client = new Curl(new None, 'https://httpbin.org');
$logger = new ConsoleLogger;

// Add logging middleware
$loggingMiddleware = new LoggingMiddleware(
    $logger,
    requestLevel: LogLevel::INFO,
    responseLevel: LogLevel::INFO,
    errorLevel: LogLevel::ERROR
);
$client->addMiddleware($loggingMiddleware);

$response = $client->get('/get');
echo "\n";

// Example 3: Rate Limiting Middleware
echo "=== Example 3: Rate Limiting Middleware ===\n";

$client = new Curl(new None, 'https://httpbin.org');

// Allow 2 requests per second
$limiter = new RateLimiter(maxRequests: 2, perSeconds: 1);
$rateLimitMiddleware = new RateLimitMiddleware($limiter);
$client->addMiddleware($rateLimitMiddleware);

echo "Making 3 requests (rate limited to 2/second)...\n";
$start = microtime(true);

$client->get('/get');
echo "Request 1 complete\n";

$client->get('/get');
echo "Request 2 complete\n";

$client->get('/get');
echo "Request 3 complete (should be delayed)\n";

$duration = microtime(true) - $start;
echo 'Total time: '.round($duration, 2)."s\n\n";

// Example 4: Multiple Middleware (Execution Order)
echo "=== Example 4: Multiple Middleware ===\n";

class LogOrderMiddleware implements MiddlewareInterface
{
    public function __construct(private string $name) {}

    public function handle(
        string $method,
        string $uri,
        array $data,
        array $headers,
        callable $next
    ): \Psr\Http\Message\ResponseInterface {
        echo "{$this->name}: Before request\n";
        $response = $next($method, $uri, $data, $headers);
        echo "{$this->name}: After response\n";

        return $response;
    }
}

$client = new Curl(new None, 'https://httpbin.org');

// Middleware executes in order: first added = outermost layer
$client->addMiddleware(new LogOrderMiddleware('Middleware 1'));
$client->addMiddleware(new LogOrderMiddleware('Middleware 2'));
$client->addMiddleware(new LogOrderMiddleware('Middleware 3'));

$client->get('/get');
echo "\n";

// Example 5: Authentication Middleware
echo "=== Example 5: Custom Authentication Middleware ===\n";

class DynamicAuthMiddleware implements MiddlewareInterface
{
    private $tokenProvider;

    public function __construct(callable $tokenProvider)
    {
        $this->tokenProvider = $tokenProvider;
    }

    public function handle(
        string $method,
        string $uri,
        array $data,
        array $headers,
        callable $next
    ): \Psr\Http\Message\ResponseInterface {
        // Get fresh token for each request
        $token = ($this->tokenProvider)();
        $headers['Authorization'] = "Bearer $token";

        return $next($method, $uri, $data, $headers);
    }
}

$client = new Curl(new None, 'https://httpbin.org');

// Token provider that could fetch from cache, refresh, etc.
$tokenProvider = function () {
    return 'fresh-token-'.time();
};

$client->addMiddleware(new DynamicAuthMiddleware($tokenProvider));
$response = $client->get('/headers');

echo "Request sent with dynamic token\n";
