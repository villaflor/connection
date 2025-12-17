<?php

require_once __DIR__.'/../vendor/autoload.php';

use Villaflor\Connection\Adapter\Curl;
use Villaflor\Connection\Auth\None;
use Villaflor\Connection\Events\EventDispatcher;
use Villaflor\Connection\Events\RequestFailedEvent;
use Villaflor\Connection\Events\RequestSendingEvent;
use Villaflor\Connection\Events\ResponseReceivedEvent;
use Villaflor\Connection\Middleware\EventMiddleware;

// Example 1: Basic Event Listening
echo "=== Example 1: Basic Event Listening ===\n";

$dispatcher = new EventDispatcher;
$client = new Curl(new None, 'https://httpbin.org');

// Listen for request events
$dispatcher->listen('request.sending', function (RequestSendingEvent $event) {
    echo "Sending {$event->method} request to {$event->uri}\n";
});

// Listen for response events
$dispatcher->listen('response.received', function (ResponseReceivedEvent $event) {
    echo "Received {$event->response->getStatusCode()} response in {$event->duration}s\n";
});

// Add event middleware to the client
$client->addMiddleware(new EventMiddleware($dispatcher));

$response = $client->get('/get');
echo "\n";

// Example 2: Request Timing and Performance Monitoring
echo "=== Example 2: Performance Monitoring ===\n";

$dispatcher = new EventDispatcher;
$client = new Curl(new None, 'https://httpbin.org');

$requestTimings = [];

$dispatcher->listen('request.sending', function (RequestSendingEvent $event) {
    echo "[PERF] Starting request: {$event->method} {$event->uri}\n";
});

$dispatcher->listen('response.received', function (ResponseReceivedEvent $event) use (&$requestTimings) {
    $duration = round($event->duration * 1000, 2); // Convert to milliseconds
    $requestTimings[] = [
        'uri' => $event->uri,
        'duration' => $duration,
        'status' => $event->response->getStatusCode(),
    ];
    echo "[PERF] Request completed in {$duration}ms\n";
});

$client->addMiddleware(new EventMiddleware($dispatcher));

// Make several requests
$client->get('/get');
$client->get('/delay/1'); // This will be slower
$client->post('/post', ['test' => 'data']);

echo "\nPerformance Summary:\n";
foreach ($requestTimings as $timing) {
    echo "- {$timing['uri']}: {$timing['duration']}ms (Status: {$timing['status']})\n";
}
echo "\n";

// Example 3: Error Handling and Logging
echo "=== Example 3: Error Handling ===\n";

$dispatcher = new EventDispatcher;
$client = new Curl(new None, 'https://httpbin.org');

$dispatcher->listen('request.failed', function (RequestFailedEvent $event) {
    echo "[ERROR] Request failed: {$event->method} {$event->uri}\n";
    echo "[ERROR] Error: {$event->exception->getMessage()}\n";
    echo "[ERROR] Duration before failure: {$event->duration}s\n";
});

$client->addMiddleware(new EventMiddleware($dispatcher));

try {
    // This should trigger a 404 error
    $response = $client->get('/status/404');
} catch (Exception $e) {
    echo "Caught exception: {$e->getMessage()}\n";
}
echo "\n";

// Example 4: Request/Response Inspection
echo "=== Example 4: Request/Response Inspection ===\n";

$dispatcher = new EventDispatcher;
$client = new Curl(new None, 'https://httpbin.org');

$dispatcher->listen('request.sending', function (RequestSendingEvent $event) {
    echo "Request Headers:\n";
    foreach ($event->headers as $name => $value) {
        echo "  {$name}: {$value}\n";
    }
    if (! empty($event->data)) {
        echo 'Request Data: '.json_encode($event->data)."\n";
    }
});

$dispatcher->listen('response.received', function (ResponseReceivedEvent $event) {
    echo "\nResponse:\n";
    echo "  Status: {$event->response->getStatusCode()}\n";
    echo '  Content-Type: '.$event->response->getHeaderLine('Content-Type')."\n";
    echo '  Body length: '.strlen($event->response->getBody()->getContents())." bytes\n";
});

$client->addMiddleware(new EventMiddleware($dispatcher));

$client->post('/post', ['name' => 'John Doe', 'email' => 'john@example.com']);
echo "\n";

// Example 5: Multiple Listeners for Same Event
echo "=== Example 5: Multiple Listeners ===\n";

$dispatcher = new EventDispatcher;
$client = new Curl(new None, 'https://httpbin.org');

// First listener: console logging
$dispatcher->listen('request.sending', function (RequestSendingEvent $event) {
    echo "[CONSOLE] Request: {$event->method} {$event->uri}\n";
});

// Second listener: simulated analytics
$dispatcher->listen('request.sending', function (RequestSendingEvent $event) {
    echo "[ANALYTICS] Track API call: {$event->uri}\n";
});

// Third listener: simulated audit log
$dispatcher->listen('request.sending', function (RequestSendingEvent $event) {
    echo "[AUDIT] User action: API request to {$event->uri}\n";
});

$client->addMiddleware(new EventMiddleware($dispatcher));

$client->get('/get');
echo "\n";

// Example 6: Conditional Event Handling
echo "=== Example 6: Conditional Event Handling ===\n";

$dispatcher = new EventDispatcher;
$client = new Curl(new None, 'https://httpbin.org');

$dispatcher->listen('response.received', function (ResponseReceivedEvent $event) {
    $statusCode = $event->response->getStatusCode();

    if ($statusCode >= 200 && $statusCode < 300) {
        echo "[SUCCESS] Request successful: {$event->uri}\n";
    } elseif ($statusCode >= 400 && $statusCode < 500) {
        echo "[WARNING] Client error: {$statusCode} for {$event->uri}\n";
    } elseif ($statusCode >= 500) {
        echo "[CRITICAL] Server error: {$statusCode} for {$event->uri}\n";
    }

    // Alert on slow requests
    if ($event->duration > 1.0) {
        echo "[ALERT] Slow request detected: {$event->uri} took {$event->duration}s\n";
    }
});

$client->addMiddleware(new EventMiddleware($dispatcher));

$client->get('/get');
$client->get('/delay/2'); // Slow request
try {
    $client->get('/status/404'); // Client error
} catch (Exception $e) {
    // Handle error
}
echo "\n";

// Example 7: Event Lifecycle - All Events Together
echo "=== Example 7: Complete Event Lifecycle ===\n";

$dispatcher = new EventDispatcher;
$client = new Curl(new None, 'https://httpbin.org');

echo "Registering event listeners...\n";

$dispatcher->listen('request.sending', function (RequestSendingEvent $event) {
    echo "1. Request sending: {$event->method} {$event->uri}\n";
});

$dispatcher->listen('response.received', function (ResponseReceivedEvent $event) {
    echo "2. Response received: Status {$event->response->getStatusCode()}\n";
});

$dispatcher->listen('request.failed', function (RequestFailedEvent $event) {
    echo "2. Request failed: {$event->exception->getMessage()}\n";
});

$client->addMiddleware(new EventMiddleware($dispatcher));

echo "\nSuccessful request:\n";
$client->get('/get');

echo "\nFailed request:\n";
try {
    $client->get('/status/500');
} catch (Exception $e) {
    // Expected
}

echo "\n";

// Example 8: Removing Event Listeners
echo "=== Example 8: Managing Listeners ===\n";

$dispatcher = new EventDispatcher;

$dispatcher->listen('request.sending', function (RequestSendingEvent $event) {
    echo "This listener is active\n";
});

echo 'Has listeners: '.($dispatcher->hasListeners('request.sending') ? 'Yes' : 'No')."\n";

// Remove all listeners for an event
$dispatcher->forget('request.sending');

echo 'After forget, has listeners: '.($dispatcher->hasListeners('request.sending') ? 'Yes' : 'No')."\n";
echo "\n";

echo "Event examples complete!\n";
