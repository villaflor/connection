<?php

namespace Villaflor\Connection\Events;

/**
 * Event dispatched before a request is sent.
 */
class RequestSendingEvent extends Event
{
    public function __construct(
        public readonly string $method,
        public readonly string $uri,
        public readonly array $data,
        public readonly array $headers
    ) {}

    public function getName(): string
    {
        return 'request.sending';
    }
}
