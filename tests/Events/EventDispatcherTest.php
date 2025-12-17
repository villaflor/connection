<?php

use Villaflor\Connection\Events\EventDispatcher;
use Villaflor\Connection\Events\RequestSendingEvent;

it('can register and dispatch events', function () {
    $dispatcher = new EventDispatcher;
    $called = false;

    $dispatcher->listen('request.sending', function ($event) use (&$called) {
        $called = true;
        expect($event)->toBeInstanceOf(RequestSendingEvent::class);
    });

    $event = new RequestSendingEvent('GET', '/test', [], []);
    $dispatcher->dispatch($event);

    expect($called)->toBeTrue();
});

it('can register multiple listeners for the same event', function () {
    $dispatcher = new EventDispatcher;
    $callCount = 0;

    $dispatcher->listen('request.sending', function () use (&$callCount) {
        $callCount++;
    });

    $dispatcher->listen('request.sending', function () use (&$callCount) {
        $callCount++;
    });

    $event = new RequestSendingEvent('GET', '/test', [], []);
    $dispatcher->dispatch($event);

    expect($callCount)->toBe(2);
});

it('does not call listeners for different events', function () {
    $dispatcher = new EventDispatcher;
    $called = false;

    $dispatcher->listen('other.event', function () use (&$called) {
        $called = true;
    });

    $event = new RequestSendingEvent('GET', '/test', [], []);
    $dispatcher->dispatch($event);

    expect($called)->toBeFalse();
});

it('can forget event listeners', function () {
    $dispatcher = new EventDispatcher;
    $called = false;

    $dispatcher->listen('request.sending', function () use (&$called) {
        $called = true;
    });

    $dispatcher->forget('request.sending');

    $event = new RequestSendingEvent('GET', '/test', [], []);
    $dispatcher->dispatch($event);

    expect($called)->toBeFalse();
});

it('can check if event has listeners', function () {
    $dispatcher = new EventDispatcher;

    expect($dispatcher->hasListeners('request.sending'))->toBeFalse();

    $dispatcher->listen('request.sending', function () {});

    expect($dispatcher->hasListeners('request.sending'))->toBeTrue();
});

it('passes event data to listeners', function () {
    $dispatcher = new EventDispatcher;
    $receivedMethod = null;
    $receivedUri = null;

    $dispatcher->listen('request.sending', function ($event) use (&$receivedMethod, &$receivedUri) {
        $receivedMethod = $event->method;
        $receivedUri = $event->uri;
    });

    $event = new RequestSendingEvent('POST', '/api/test', ['foo' => 'bar'], ['X-Test' => 'value']);
    $dispatcher->dispatch($event);

    expect($receivedMethod)->toBe('POST');
    expect($receivedUri)->toBe('/api/test');
});
