<?php

require_once __DIR__.'/../vendor/autoload.php';

use Villaflor\Connection\Adapter\Curl;
use Villaflor\Connection\Auth\APIToken;
use Villaflor\Connection\ConnectionBuilder;

// Example 1: Basic GET request with Bearer token authentication
echo "=== Example 1: Basic GET Request ===\n";

$client = new Curl(
    new APIToken('your-api-token-here'),
    'https://api.example.com'
);

try {
    $response = $client->get('/users');
    echo 'Status: '.$response->getStatusCode()."\n";
    echo 'Body: '.$response->getBody()."\n\n";
} catch (Exception $e) {
    echo 'Error: '.$e->getMessage()."\n\n";
}

// Example 2: POST request with JSON body
echo "=== Example 2: POST Request with JSON ===\n";

$data = [
    'name' => 'John Doe',
    'email' => 'john@example.com',
];

try {
    $response = $client->post('/users', $data);
    echo 'Status: '.$response->getStatusCode()."\n";
    echo 'Body: '.$response->getBody()."\n\n";
} catch (Exception $e) {
    echo 'Error: '.$e->getMessage()."\n\n";
}

// Example 3: Using convenience JSON methods
echo "=== Example 3: Convenience JSON Methods ===\n";

try {
    // Automatically decodes JSON response
    $userData = $client->getJson('/users/1');
    print_r($userData);

    // POST with automatic JSON encoding/decoding
    $newUser = $client->postJson('/users', [
        'name' => 'Jane Doe',
        'email' => 'jane@example.com',
    ]);
    print_r($newUser);
} catch (Exception $e) {
    echo 'Error: '.$e->getMessage()."\n\n";
}

// Example 4: Using the ConnectionBuilder for fluent API
echo "=== Example 4: Using ConnectionBuilder ===\n";

$client = ConnectionBuilder::create()
    ->withBaseUri('https://api.example.com')
    ->withBearerToken('your-token-here')
    ->withTimeout(60)
    ->withConnectTimeout(10)
    ->build();

try {
    $response = $client->get('/users');
    echo 'Status: '.$response->getStatusCode()."\n\n";
} catch (Exception $e) {
    echo 'Error: '.$e->getMessage()."\n\n";
}

// Example 5: PUT, PATCH, DELETE requests
echo "=== Example 5: Other HTTP Methods ===\n";

try {
    // PUT request
    $response = $client->put('/users/1', [
        'name' => 'Updated Name',
    ]);
    echo 'PUT Status: '.$response->getStatusCode()."\n";

    // PATCH request
    $response = $client->patch('/users/1', [
        'email' => 'newemail@example.com',
    ]);
    echo 'PATCH Status: '.$response->getStatusCode()."\n";

    // DELETE request
    $response = $client->delete('/users/1');
    echo 'DELETE Status: '.$response->getStatusCode()."\n\n";
} catch (Exception $e) {
    echo 'Error: '.$e->getMessage()."\n\n";
}

// Example 6: Custom headers
echo "=== Example 6: Custom Headers ===\n";

try {
    $response = $client->get('/users', [], [
        'X-Custom-Header' => 'custom-value',
        'Accept-Language' => 'en-US',
    ]);
    echo 'Status: '.$response->getStatusCode()."\n";
} catch (Exception $e) {
    echo 'Error: '.$e->getMessage()."\n";
}
