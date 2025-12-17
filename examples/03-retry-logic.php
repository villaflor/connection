<?php

require_once __DIR__.'/../vendor/autoload.php';

use Villaflor\Connection\Adapter\Curl;
use Villaflor\Connection\Auth\None;
use Villaflor\Connection\Retry\RetryConfig;

// Example 1: Basic retry configuration
echo "=== Example 1: Basic Retry Configuration ===\n";

$client = new Curl(new None, 'https://api.example.com');

// Configure retry with default settings
// - 3 max attempts
// - Retries on: 408, 429, 500, 502, 503, 504
// - Exponential backoff: 1s, 2s, 4s, 8s... (max 30s)
$retryConfig = new RetryConfig;
$client->setRetryConfig($retryConfig);

try {
    $response = $client->get('/unstable-endpoint');
    echo "Success after retries!\n\n";
} catch (Exception $e) {
    echo 'Failed after all retries: '.$e->getMessage()."\n\n";
}

// Example 2: Custom retry configuration
echo "=== Example 2: Custom Retry Configuration ===\n";

$client = new Curl(new None, 'https://api.example.com');

// Custom configuration:
// - 5 max attempts
// - Only retry on 503 (Service Unavailable)
// - Exponential backoff enabled
// - Start with 2 second delay
// - Max delay of 60 seconds
$retryConfig = new RetryConfig(
    maxAttempts: 5,
    retryableStatusCodes: [503],
    exponentialBackoff: true,
    baseDelay: 2000,  // 2 seconds
    maxDelay: 60000   // 60 seconds
);

$client->setRetryConfig($retryConfig);

try {
    $response = $client->get('/endpoint');
    echo 'Status: '.$response->getStatusCode()."\n\n";
} catch (Exception $e) {
    echo 'Error: '.$e->getMessage()."\n\n";
}

// Example 3: Linear backoff (no exponential growth)
echo "=== Example 3: Linear Backoff ===\n";

$client = new Curl(new None, 'https://api.example.com');

// Fixed 1 second delay between retries
$retryConfig = new RetryConfig(
    maxAttempts: 3,
    retryableStatusCodes: [500, 502, 503, 504],
    exponentialBackoff: false,  // Linear backoff
    baseDelay: 1000  // Always 1 second
);

$client->setRetryConfig($retryConfig);

// Example 4: Aggressive retry for rate limits
echo "=== Example 4: Rate Limit Retry Configuration ===\n";

$client = new Curl(new None, 'https://api.example.com');

// Specifically handle rate limits (429)
$retryConfig = new RetryConfig(
    maxAttempts: 10,  // Many attempts
    retryableStatusCodes: [429],  // Only rate limits
    exponentialBackoff: true,
    baseDelay: 1000,
    maxDelay: 120000  // Up to 2 minutes
);

$client->setRetryConfig($retryConfig);

// Example 5: Understanding retry behavior
echo "=== Example 5: Retry Behavior Demonstration ===\n";

$retryConfig = new RetryConfig(
    maxAttempts: 4,
    retryableStatusCodes: [500],
    exponentialBackoff: true,
    baseDelay: 1000,
    maxDelay: 10000
);

echo "Retry delays with exponential backoff:\n";
for ($attempt = 1; $attempt <= 4; $attempt++) {
    $delay = $retryConfig->getDelay($attempt);
    echo "Attempt $attempt: Wait {$delay}ms before retry\n";
}

echo "\nRetryable status codes:\n";
foreach ([200, 404, 429, 500, 503] as $code) {
    $retryable = $retryConfig->shouldRetry($code) ? 'YES' : 'NO';
    echo "Status $code: Retry? $retryable\n";
}

// Example 6: Combining with other features
echo "\n=== Example 6: Retry + Timeout Configuration ===\n";

$client = new Curl(new None, 'https://api.example.com');

// Set timeouts
$client->setTimeout(30);  // 30 second request timeout
$client->setConnectTimeout(5);  // 5 second connection timeout

// Set retry logic
$retryConfig = new RetryConfig(
    maxAttempts: 3,
    retryableStatusCodes: [408, 500, 502, 503, 504],
    exponentialBackoff: true,
    baseDelay: 1000,
    maxDelay: 10000
);
$client->setRetryConfig($retryConfig);

echo "Client configured with both timeouts and retries!\n";
echo "Will retry on timeout and server errors.\n";
