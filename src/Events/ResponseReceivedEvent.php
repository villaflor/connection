<?php

namespace Villaflor\Connection\Events;

use Psr\Http\Message\ResponseInterface;

/**
 * Event dispatched after a successful response is received.
 */
class ResponseReceivedEvent extends Event
{
    public function __construct(
        public readonly string $method,
        public readonly string $uri,
        public readonly ResponseInterface $response,
        public readonly float $duration
    ) {}

    public function getName(): string
    {
        return 'response.received';
    }
}
