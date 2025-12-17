<?php

namespace Villaflor\Connection\Events;

/**
 * Base event class for HTTP client events.
 */
abstract class Event
{
    /**
     * Get the event name.
     */
    abstract public function getName(): string;
}
