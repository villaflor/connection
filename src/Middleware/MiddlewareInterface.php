<?php

namespace Villaflor\Connection\Middleware;

use Psr\Http\Message\ResponseInterface;

/**
 * Interface for HTTP request/response middleware.
 *
 * Middleware can modify requests before they are sent, responses after they are
 * received, or handle errors that occur during the request/response cycle.
 */
interface MiddlewareInterface
{
    /**
     * Process an HTTP request and/or response.
     *
     * This method is called before the request is sent. The middleware can:
     * - Modify the request parameters (method, uri, data, headers)
     * - Call $next to continue the request chain
     * - Modify the response after $next returns
     * - Handle exceptions from $next
     *
     * @param  string  $method  HTTP method
     * @param  string  $uri  Request URI
     * @param  array  $data  Request data
     * @param  array  $headers  Request headers
     * @param  callable  $next  Next handler in the chain
     * @return ResponseInterface The response
     */
    public function handle(
        string $method,
        string $uri,
        array $data,
        array $headers,
        callable $next
    ): ResponseInterface;
}
