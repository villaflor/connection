<?php

namespace Villaflor\Connection\Events;

/**
 * Simple event dispatcher for HTTP client events.
 */
class EventDispatcher
{
    /** @var array<string, array<callable>> */
    private array $listeners = [];

    /**
     * Register an event listener.
     *
     * @param  string  $eventName  The event name to listen for
     * @param  callable  $listener  The listener callback
     */
    public function listen(string $eventName, callable $listener): void
    {
        if (! isset($this->listeners[$eventName])) {
            $this->listeners[$eventName] = [];
        }

        $this->listeners[$eventName][] = $listener;
    }

    /**
     * Dispatch an event to all registered listeners.
     *
     * @param  Event  $event  The event to dispatch
     */
    public function dispatch(Event $event): void
    {
        $eventName = $event->getName();

        if (! isset($this->listeners[$eventName])) {
            return;
        }

        foreach ($this->listeners[$eventName] as $listener) {
            $listener($event);
        }
    }

    /**
     * Remove all listeners for a specific event.
     *
     * @param  string  $eventName  The event name
     */
    public function forget(string $eventName): void
    {
        unset($this->listeners[$eventName]);
    }

    /**
     * Check if an event has listeners.
     *
     * @param  string  $eventName  The event name
     */
    public function hasListeners(string $eventName): bool
    {
        return isset($this->listeners[$eventName]) && count($this->listeners[$eventName]) > 0;
    }
}
