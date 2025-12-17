<?php

namespace Villaflor\Connection\Events;

use Exception;

/**
 * Event dispatched when a request fails.
 */
class RequestFailedEvent extends Event
{
    public function __construct(
        public readonly string $method,
        public readonly string $uri,
        public readonly Exception $exception,
        public readonly float $duration
    ) {}

    public function getName(): string
    {
        return 'request.failed';
    }
}
