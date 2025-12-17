<?php

require_once __DIR__.'/../vendor/autoload.php';

use Villaflor\Connection\Adapter\Curl;
use Villaflor\Connection\Auth\APIToken;
use Villaflor\Connection\Auth\None;
use Villaflor\Connection\ConnectionBuilder;

// Example 1: Basic Proxy Configuration
echo "=== Example 1: Basic Proxy Configuration ===\n";

$client = new Curl(new None, 'https://api.example.com');

// Configure HTTP proxy
$client->setProxy('proxy.example.com:8080');

echo "Client configured to use proxy: proxy.example.com:8080\n";
echo "All requests will now go through this proxy\n\n";

// Example 2: Proxy with Authentication
echo "=== Example 2: Proxy with Authentication ===\n";

$client = new Curl(new None, 'https://api.example.com');

// Configure proxy with username:password authentication
$client->setProxy('proxy.example.com:8080', 'username:password');

echo "Client configured with authenticated proxy\n";
echo "Proxy: proxy.example.com:8080\n";
echo "Authentication: username:password\n\n";

// Example 3: Using ConnectionBuilder with Proxy
echo "=== Example 3: ConnectionBuilder with Proxy ===\n";

$client = ConnectionBuilder::create()
    ->withBaseUri('https://api.example.com')
    ->withBearerToken('your-token')
    ->withProxy('proxy.example.com:8080', 'user:pass')
    ->build();

echo "Client built with proxy configuration\n\n";

// Example 4: SSL/TLS Verification (Production Settings)
echo "=== Example 4: SSL/TLS Verification (Production) ===\n";

$client = new Curl(new None, 'https://api.example.com');

// Enable SSL verification (this is the default and recommended for production)
$client->setVerifyPeer(true);  // Verify the peer's SSL certificate
$client->setVerifyHost(true);   // Verify that the certificate matches the host

echo "SSL verification enabled (recommended for production)\n";
echo "- Peer verification: ON\n";
echo "- Host verification: ON\n\n";

// Example 5: SSL Verification Disabled (Development/Testing Only)
echo "=== Example 5: SSL Verification Disabled (Development Only) ===\n";

$client = new Curl(new None, 'https://self-signed.badssl.com');

// SECURITY WARNING: Only disable SSL verification for testing/development
// Never use this in production!
$client->setVerifyPeer(false);
$client->setVerifyHost(false);

echo "WARNING: SSL verification disabled!\n";
echo "This should ONLY be used for local development/testing.\n";
echo "NEVER disable SSL verification in production!\n\n";

// Example 6: Custom CA Bundle
echo "=== Example 6: Custom CA Bundle ===\n";

$client = new Curl(new None, 'https://api.example.com');

// Specify a custom CA certificate bundle
// Useful for self-signed certificates or custom CAs in enterprise environments
$client->setCaBundle('/path/to/custom-ca-bundle.crt');

echo "Client configured with custom CA bundle\n";
echo "CA Bundle: /path/to/custom-ca-bundle.crt\n";
echo "Use this when connecting to servers with self-signed or custom CA certificates\n\n";

// Example 7: Client SSL Certificates (Mutual TLS)
echo "=== Example 7: Client SSL Certificates (mTLS) ===\n";

$client = new Curl(new None, 'https://api.example.com');

// Configure client certificate for mutual TLS authentication
$client->setSslCert(
    '/path/to/client-cert.pem',
    '/path/to/client-key.pem'
);

echo "Client configured with SSL client certificate (mutual TLS)\n";
echo "Certificate: /path/to/client-cert.pem\n";
echo "Private Key: /path/to/client-key.pem\n";
echo "Server will verify our client certificate\n\n";

// Example 8: ConnectionBuilder with SSL Configuration
echo "=== Example 8: ConnectionBuilder with SSL ===\n";

$client = ConnectionBuilder::create()
    ->withBaseUri('https://api.example.com')
    ->withBearerToken('your-token')
    ->withVerifyPeer(true)
    ->withVerifyHost(true)
    ->withCaBundle('/path/to/ca-bundle.crt')
    ->build();

echo "Client built with SSL configuration\n\n";

// Example 9: Combined Proxy and SSL Configuration
echo "=== Example 9: Combined Proxy and SSL ===\n";

$client = new Curl(
    new APIToken('your-api-token'),
    'https://api.example.com'
);

// Configure both proxy and SSL settings
$client->setProxy('proxy.corporate.com:8080', 'user:pass');
$client->setVerifyPeer(true);
$client->setVerifyHost(true);
$client->setCaBundle('/etc/ssl/certs/ca-bundle.crt');

echo "Client configured with both proxy and SSL:\n";
echo "- Proxy: proxy.corporate.com:8080 (authenticated)\n";
echo "- SSL verification: Enabled\n";
echo "- CA Bundle: /etc/ssl/certs/ca-bundle.crt\n";
echo "\nThis is a typical enterprise configuration\n\n";

// Example 10: Real-World Example - Corporate Environment
echo "=== Example 10: Real-World Corporate Setup ===\n";

// Corporate environment often requires:
// 1. Proxy for internet access
// 2. Custom CA for internal services
// 3. SSL verification enabled
// 4. Client certificates for some services

$client = ConnectionBuilder::create()
    ->withBaseUri('https://internal-api.company.com')
    ->withBearerToken('internal-service-token')
    ->withProxy('corporate-proxy.company.com:3128', 'myuser:mypass')
    ->withCaBundle('/etc/ssl/company-ca-bundle.crt')
    ->withVerifyPeer(true)
    ->withVerifyHost(true)
    ->withTimeout(30)
    ->withConnectTimeout(10)
    ->build();

// For services requiring mutual TLS
$client->setSslCert(
    '/etc/ssl/company-client-cert.pem',
    '/etc/ssl/company-client-key.pem'
);

echo "Enterprise client configured:\n";
echo "- Base URI: https://internal-api.company.com\n";
echo "- Authentication: Bearer token\n";
echo "- Proxy: corporate-proxy.company.com:3128 (with auth)\n";
echo "- CA Bundle: /etc/ssl/company-ca-bundle.crt\n";
echo "- Client Certificate: Configured for mutual TLS\n";
echo "- Timeouts: 30s request, 10s connect\n";
echo "- SSL Verification: Fully enabled\n\n";

// Example 11: Testing with Real SSL Endpoints
echo "=== Example 11: Testing with Real Endpoints ===\n";

// Test with a known good SSL endpoint
$client = new Curl(new None, 'https://httpbin.org');
$client->setVerifyPeer(true);
$client->setVerifyHost(true);

try {
    $response = $client->get('/get');
    echo "✓ Successfully connected to httpbin.org with SSL verification\n";
    echo '  Status: '.$response->getStatusCode()."\n";
} catch (Exception $e) {
    echo '✗ Connection failed: '.$e->getMessage()."\n";
}

// Test with a self-signed certificate endpoint (will fail with verification on)
echo "\nTesting self-signed certificate (should fail with verification):\n";
$client = new Curl(new None, 'https://self-signed.badssl.com');
$client->setVerifyPeer(true);

try {
    $response = $client->get('/');
    echo "✓ Connected (unexpected)\n";
} catch (Exception $e) {
    echo "✗ Expected failure: Self-signed certificate rejected\n";
    echo "  This is correct behavior with SSL verification enabled\n";
}

// Now disable verification for the self-signed cert
echo "\nTesting with verification disabled:\n";
$client->setVerifyPeer(false);
$client->setVerifyHost(false);

try {
    $response = $client->get('/');
    echo "✓ Connected to self-signed endpoint (verification disabled)\n";
    echo "  WARNING: Only do this in development!\n";
} catch (Exception $e) {
    echo '✗ Connection failed: '.$e->getMessage()."\n";
}

echo "\n";

// Example 12: Best Practices Summary
echo "=== Example 12: Best Practices ===\n";
echo '
1. PRODUCTION:
   - Always enable SSL verification (verifyPeer=true, verifyHost=true)
   - Use system CA bundle or known-good custom CA bundle
   - Use client certificates for sensitive services (mutual TLS)
   - Keep SSL libraries up-to-date

2. DEVELOPMENT:
   - You may disable SSL verification for local testing only
   - Use custom CA bundle for self-signed certificates when possible
   - Never commit disabled SSL verification to version control

3. CORPORATE/ENTERPRISE:
   - Configure proxy with proper authentication
   - Use company CA bundle for internal certificates
   - Implement client certificates for internal services
   - Set appropriate timeouts for your network environment

4. SECURITY:
   - Never disable SSL verification in production
   - Protect private keys with proper file permissions
   - Rotate client certificates regularly
   - Monitor certificate expiration dates
';

echo "Proxy and SSL examples complete!\n";
