<?php

use Psr\Log\LoggerInterface;
use Villaflor\Connection\Http\Response;
use Villaflor\Connection\Http\Stream;
use Villaflor\Connection\Middleware\LoggingMiddleware;

it('logs successful request and response', function () {
    $logger = $this->getMockBuilder(LoggerInterface::class)->getMock();

    $callCount = 0;
    $logger->expects($this->exactly(2))
        ->method('log')
        ->willReturnCallback(function ($level, $message, $context) use (&$callCount) {
            if ($callCount === 0) {
                // First call - request log
                expect($level)->toBe('info');
                expect($message)->toBe('HTTP Request');
                expect($context['method'])->toBe('GET');
                expect($context['uri'])->toBe('/test');
                expect($context['data'])->toBeArray();
                expect($context['headers'])->toBeArray();
            } elseif ($callCount === 1) {
                // Second call - response log
                expect($level)->toBe('info');
                expect($message)->toBe('HTTP Response');
                expect($context['method'])->toBe('GET');
                expect($context['uri'])->toBe('/test');
                expect($context['status'])->toBe(200);
                expect($context['duration'])->toContain('ms');
            }
            $callCount++;
        });

    $middleware = new LoggingMiddleware($logger);

    $next = function ($method, $uri, $data, $headers) {
        return new Response(200, [], new Stream('test'), 'OK');
    };

    $response = $middleware->handle('GET', '/test', [], [], $next);

    expect($response->getStatusCode())->toBe(200);
});

it('logs failed request with error', function () {
    $logger = $this->getMockBuilder(LoggerInterface::class)->getMock();

    $callCount = 0;
    $logger->expects($this->exactly(2))
        ->method('log')
        ->willReturnCallback(function ($level, $message, $context) use (&$callCount) {
            if ($callCount === 0) {
                // First call - request log
                expect($level)->toBe('info');
                expect($message)->toBe('HTTP Request');
            } elseif ($callCount === 1) {
                // Second call - error log
                expect($level)->toBe('error');
                expect($message)->toBe('HTTP Request Failed');
                expect($context['error'])->toBe('Test exception');
                expect($context['duration'])->toContain('ms');
            }
            $callCount++;
        });

    $middleware = new LoggingMiddleware($logger);

    $next = function ($method, $uri, $data, $headers) {
        throw new Exception('Test exception');
    };

    $this->expectException(Exception::class);
    $middleware->handle('GET', '/test', [], [], $next);
});

it('sanitizes sensitive headers', function () {
    $logger = $this->getMockBuilder(LoggerInterface::class)->getMock();

    // Capture the logged context to verify header sanitization
    $loggedContext = null;
    $logger->expects($this->exactly(2))
        ->method('log')
        ->willReturnCallback(function ($level, $message, $context) use (&$loggedContext) {
            if ($message === 'HTTP Request') {
                $loggedContext = $context;
            }
        });

    $middleware = new LoggingMiddleware($logger);

    $next = function ($method, $uri, $data, $headers) {
        return new Response(200, [], new Stream('test'), 'OK');
    };

    $headers = [
        'Authorization' => 'Bearer secret-token',
        'X-API-Key' => 'secret-key',
        'Content-Type' => 'application/json',
    ];

    $middleware->handle('GET', '/test', [], $headers, $next);

    expect($loggedContext['headers']['Authorization'])->toBe('***REDACTED***');
    expect($loggedContext['headers']['X-API-Key'])->toBe('***REDACTED***');
    expect($loggedContext['headers']['Content-Type'])->toBe('application/json');
});

it('can use custom log levels', function () {
    $logger = $this->getMockBuilder(LoggerInterface::class)->getMock();

    $callCount = 0;
    $logger->expects($this->exactly(2))
        ->method('log')
        ->willReturnCallback(function ($level, $message, $context) use (&$callCount) {
            if ($callCount === 0) {
                expect($level)->toBe('debug');
                expect($message)->toBe('HTTP Request');
            } elseif ($callCount === 1) {
                expect($level)->toBe('debug');
                expect($message)->toBe('HTTP Response');
            }
            $callCount++;
        });

    $middleware = new LoggingMiddleware(
        $logger,
        requestLevel: 'debug',
        responseLevel: 'debug',
        errorLevel: 'critical'
    );

    $next = function ($method, $uri, $data, $headers) {
        return new Response(200, [], new Stream('test'), 'OK');
    };

    $middleware->handle('GET', '/test', [], [], $next);
});
