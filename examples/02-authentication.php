<?php

require_once __DIR__.'/../vendor/autoload.php';

use Villaflor\Connection\Adapter\Curl;
use Villaflor\Connection\Auth\APIKey;
use Villaflor\Connection\Auth\APIToken;
use Villaflor\Connection\Auth\CustomHeaders;
use Villaflor\Connection\Auth\GoogleServiceAccount;
use Villaflor\Connection\Auth\None;
use Villaflor\Connection\Auth\UserServiceKey;
use Villaflor\Connection\ConnectionBuilder;

// Example 1: Bearer Token Authentication
echo "=== Example 1: Bearer Token (API Token) ===\n";

$client = new Curl(
    new APIToken('your-bearer-token'),
    'https://api.example.com'
);
// Adds: Authorization: Bearer your-bearer-token

// Example 2: API Key Authentication
echo "=== Example 2: API Key Authentication ===\n";

$client = new Curl(
    new APIKey('user@example.com', 'your-api-key'),
    'https://api.example.com'
);
// Adds: Authorization: Basic base64(email:apikey)

// Example 3: Custom Headers Authentication
echo "=== Example 3: Custom Headers ===\n";

$client = new Curl(
    new CustomHeaders([
        'X-API-Key' => 'your-api-key',
        'X-Client-ID' => 'your-client-id',
    ]),
    'https://api.example.com'
);

// Example 4: User Service Key Authentication
echo "=== Example 4: User Service Key ===\n";

$client = new Curl(
    new UserServiceKey('your-service-key'),
    'https://api.example.com'
);
// Adds: UserServiceKey: your-service-key

// Example 5: Google Service Account
echo "=== Example 5: Google Service Account ===\n";

$serviceAccountConfig = [
    'type' => 'service_account',
    'project_id' => 'your-project',
    'private_key_id' => 'key-id',
    'private_key' => "-----BEGIN PRIVATE KEY-----\n...\n-----END PRIVATE KEY-----\n",
    'client_email' => 'service@your-project.iam.gserviceaccount.com',
    'client_id' => '123456789',
    'auth_uri' => 'https://accounts.google.com/o/oauth2/auth',
    'token_uri' => 'https://oauth2.googleapis.com/token',
];

$client = new Curl(
    new GoogleServiceAccount($serviceAccountConfig, 'https://www.googleapis.com/auth/cloud-platform'),
    'https://api.example.com'
);

// You can customize token duration (default is 3600 seconds)
$auth = new GoogleServiceAccount($serviceAccountConfig, 'https://www.googleapis.com/auth/cloud-platform');
$auth->setTokenDuration(7200); // 2 hours

// Example 6: No Authentication
echo "=== Example 6: No Authentication ===\n";

$client = new Curl(
    new None,
    'https://api.example.com'
);

// Example 7: Using ConnectionBuilder with different auth methods
echo "=== Example 7: ConnectionBuilder with Auth ===\n";

// Bearer token
$client = ConnectionBuilder::create()
    ->withBaseUri('https://api.example.com')
    ->withBearerToken('your-token')
    ->build();

// API Key
$client = ConnectionBuilder::create()
    ->withBaseUri('https://api.example.com')
    ->withApiKey('user@example.com', 'api-key')
    ->build();

// Custom headers
$client = ConnectionBuilder::create()
    ->withBaseUri('https://api.example.com')
    ->withCustomHeaders([
        'X-API-Key' => 'key',
    ])
    ->build();

// Google Service Account
$client = ConnectionBuilder::create()
    ->withBaseUri('https://api.example.com')
    ->withGoogleServiceAccount($serviceAccountConfig, 'scope')
    ->build();

echo "Authentication examples complete!\n";
