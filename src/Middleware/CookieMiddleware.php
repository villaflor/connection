<?php

namespace Villaflor\Connection\Middleware;

use Psr\Http\Message\ResponseInterface;
use Villaflor\Connection\Cookie\CookieJar;

/**
 * Middleware for automatic cookie management.
 *
 * This middleware automatically:
 * - Adds cookies from the jar to outgoing requests
 * - Stores cookies from Set-Cookie headers in responses
 */
class CookieMiddleware implements MiddlewareInterface
{
    public function __construct(
        private readonly CookieJar $cookieJar
    ) {}

    public function handle(
        string $method,
        string $uri,
        array $data,
        array $headers,
        callable $next
    ): ResponseInterface {
        // Extract domain and path from URI
        $parsedUrl = parse_url($uri);
        $domain = $parsedUrl['host'] ?? '';
        $path = $parsedUrl['path'] ?? '/';

        // Add cookies to request headers
        $cookieHeader = $this->cookieJar->getCookieHeader($domain, $path);
        if ($cookieHeader !== null) {
            $headers['Cookie'] = $cookieHeader;
        }

        // Execute request
        $response = $next($method, $uri, $data, $headers);

        // Store cookies from response
        if ($response->hasHeader('Set-Cookie')) {
            $setCookieHeaders = $response->getHeader('Set-Cookie');
            $this->cookieJar->addFromHeaders($setCookieHeaders);
        }

        return $response;
    }

    /**
     * Get the cookie jar.
     */
    public function getCookieJar(): CookieJar
    {
        return $this->cookieJar;
    }
}
