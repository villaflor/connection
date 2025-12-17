<?php

use Villaflor\Connection\Events\EventDispatcher;
use Villaflor\Connection\Events\RequestFailedEvent;
use Villaflor\Connection\Events\RequestSendingEvent;
use Villaflor\Connection\Events\ResponseReceivedEvent;
use Villaflor\Connection\Http\Response;
use Villaflor\Connection\Http\Stream;
use Villaflor\Connection\Middleware\EventMiddleware;

it('dispatches request sending event', function () {
    $dispatcher = new EventDispatcher;
    $middleware = new EventMiddleware($dispatcher);

    $eventFired = false;
    $dispatcher->listen('request.sending', function ($event) use (&$eventFired) {
        $eventFired = true;
        expect($event)->toBeInstanceOf(RequestSendingEvent::class);
        expect($event->method)->toBe('GET');
        expect($event->uri)->toBe('/test');
    });

    $next = function () {
        return new Response(200, [], new Stream('test'), 'OK');
    };

    $middleware->handle('GET', '/test', [], [], $next);

    expect($eventFired)->toBeTrue();
});

it('dispatches response received event', function () {
    $dispatcher = new EventDispatcher;
    $middleware = new EventMiddleware($dispatcher);

    $eventFired = false;
    $dispatcher->listen('response.received', function ($event) use (&$eventFired) {
        $eventFired = true;
        expect($event)->toBeInstanceOf(ResponseReceivedEvent::class);
        expect($event->response->getStatusCode())->toBe(200);
        expect($event->duration)->toBeGreaterThan(0);
    });

    $next = function () {
        return new Response(200, [], new Stream('test'), 'OK');
    };

    $middleware->handle('GET', '/test', [], [], $next);

    expect($eventFired)->toBeTrue();
});

it('dispatches request failed event on exception', function () {
    $dispatcher = new EventDispatcher;
    $middleware = new EventMiddleware($dispatcher);

    $eventFired = false;
    $dispatcher->listen('request.failed', function ($event) use (&$eventFired) {
        $eventFired = true;
        expect($event)->toBeInstanceOf(RequestFailedEvent::class);
        expect($event->exception->getMessage())->toBe('Test exception');
        expect($event->duration)->toBeGreaterThan(0);
    });

    $next = function () {
        throw new Exception('Test exception');
    };

    try {
        $middleware->handle('GET', '/test', [], [], $next);
    } catch (Exception $e) {
        // Expected
    }

    expect($eventFired)->toBeTrue();
});

it('measures request duration accurately', function () {
    $dispatcher = new EventDispatcher;
    $middleware = new EventMiddleware($dispatcher);

    $duration = null;
    $dispatcher->listen('response.received', function ($event) use (&$duration) {
        $duration = $event->duration;
    });

    $next = function () {
        usleep(50000); // 50ms

        return new Response(200, [], new Stream('test'), 'OK');
    };

    $middleware->handle('GET', '/test', [], [], $next);

    expect($duration)->toBeGreaterThan(0.04); // At least 40ms
});

it('integrates with real HTTP client', function () {
    $auth = $this->getMockBuilder(Villaflor\Connection\Auth\AuthInterface::class)
        ->onlyMethods(['getHeaders'])
        ->getMock();
    $auth->method('getHeaders')->willReturn([]);

    $client = new Villaflor\Connection\Adapter\Curl($auth, 'https://postman-echo.com');

    $dispatcher = new EventDispatcher;
    $middleware = new EventMiddleware($dispatcher);
    $client->addMiddleware($middleware);

    $events = [];
    $dispatcher->listen('request.sending', function ($event) use (&$events) {
        $events[] = 'sending';
    });
    $dispatcher->listen('response.received', function ($event) use (&$events) {
        $events[] = 'received';
    });

    $response = $client->get('/get');

    expect($response->getStatusCode())->toBe(200);
    expect($events)->toBe(['sending', 'received']);
});
