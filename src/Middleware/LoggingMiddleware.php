<?php

namespace Villaflor\Connection\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;

/**
 * Middleware for logging HTTP requests and responses.
 *
 * This middleware logs all HTTP requests before they are sent and responses
 * after they are received, including timing information and error details.
 */
class LoggingMiddleware implements MiddlewareInterface
{
    /**
     * Create a new logging middleware instance.
     *
     * @param  LoggerInterface  $logger  PSR-3 logger instance
     * @param  string  $requestLevel  Log level for requests (default: info)
     * @param  string  $responseLevel  Log level for responses (default: info)
     * @param  string  $errorLevel  Log level for errors (default: error)
     */
    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly string $requestLevel = LogLevel::INFO,
        private readonly string $responseLevel = LogLevel::INFO,
        private readonly string $errorLevel = LogLevel::ERROR
    ) {}

    public function handle(
        string $method,
        string $uri,
        array $data,
        array $headers,
        callable $next
    ): ResponseInterface {
        $startTime = microtime(true);

        // Log the request
        $this->logger->log($this->requestLevel, 'HTTP Request', [
            'method' => $method,
            'uri' => $uri,
            'data' => $data,
            'headers' => $this->sanitizeHeaders($headers),
        ]);

        try {
            $response = $next($method, $uri, $data, $headers);

            $duration = microtime(true) - $startTime;

            // Log the response
            $this->logger->log($this->responseLevel, 'HTTP Response', [
                'method' => $method,
                'uri' => $uri,
                'status' => $response->getStatusCode(),
                'duration' => round($duration * 1000, 2).'ms',
            ]);

            return $response;
        } catch (\Exception $e) {
            $duration = microtime(true) - $startTime;

            // Log the error
            $this->logger->log($this->errorLevel, 'HTTP Request Failed', [
                'method' => $method,
                'uri' => $uri,
                'error' => $e->getMessage(),
                'duration' => round($duration * 1000, 2).'ms',
            ]);

            throw $e;
        }
    }

    /**
     * Sanitize headers to remove sensitive information.
     *
     * @param  array  $headers  Request headers
     * @return array Sanitized headers
     */
    private function sanitizeHeaders(array $headers): array
    {
        $sensitiveHeaders = ['authorization', 'x-api-key', 'api-key', 'token'];
        $sanitized = [];

        foreach ($headers as $name => $value) {
            if (in_array(strtolower($name), $sensitiveHeaders)) {
                $sanitized[$name] = '***REDACTED***';
            } else {
                $sanitized[$name] = $value;
            }
        }

        return $sanitized;
    }
}
