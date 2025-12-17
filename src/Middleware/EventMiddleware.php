<?php

namespace Villaflor\Connection\Middleware;

use Exception;
use Psr\Http\Message\ResponseInterface;
use Villaflor\Connection\Events\EventDispatcher;
use Villaflor\Connection\Events\RequestFailedEvent;
use Villaflor\Connection\Events\RequestSendingEvent;
use Villaflor\Connection\Events\ResponseReceivedEvent;

/**
 * Middleware for dispatching HTTP lifecycle events.
 *
 * This middleware allows hooking into various points of the request/response
 * cycle for observability, debugging, and custom behavior.
 */
class EventMiddleware implements MiddlewareInterface
{
    /**
     * Create a new event middleware instance.
     *
     * @param  EventDispatcher  $dispatcher  The event dispatcher
     */
    public function __construct(
        private readonly EventDispatcher $dispatcher
    ) {}

    public function handle(
        string $method,
        string $uri,
        array $data,
        array $headers,
        callable $next
    ): ResponseInterface {
        $startTime = microtime(true);

        // Dispatch request sending event
        $this->dispatcher->dispatch(new RequestSendingEvent($method, $uri, $data, $headers));

        try {
            $response = $next($method, $uri, $data, $headers);

            $duration = microtime(true) - $startTime;

            // Dispatch response received event
            $this->dispatcher->dispatch(new ResponseReceivedEvent($method, $uri, $response, $duration));

            return $response;
        } catch (Exception $e) {
            $duration = microtime(true) - $startTime;

            // Dispatch request failed event
            $this->dispatcher->dispatch(new RequestFailedEvent($method, $uri, $e, $duration));

            throw $e;
        }
    }
}
