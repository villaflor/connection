<?php

require_once __DIR__.'/../vendor/autoload.php';

use Villaflor\Connection\Adapter\Curl;
use Villaflor\Connection\Auth\None;
use Villaflor\Connection\Cookie\Cookie;
use Villaflor\Connection\Cookie\CookieJar;
use Villaflor\Connection\Middleware\CookieMiddleware;

// Example 1: Basic Cookie Management
echo "=== Example 1: Basic Cookie Management ===\n";

$jar = new CookieJar;
$client = new Curl(new None, 'https://httpbin.org');
$client->addMiddleware(new CookieMiddleware($jar));

echo "Making request that sets cookies...\n";
// httpbin.org will typically echo cookies back
$response = $client->get('/cookies/set?session=abc123&user=john');

echo 'Cookies in jar: '.$jar->count()."\n";
echo "Making second request (cookies will be sent automatically)...\n";

$response = $client->get('/cookies');
$body = json_decode($response->getBody()->getContents(), true);
echo 'Server received cookies: '.json_encode($body['cookies'])."\n\n";

// Example 2: Manual Cookie Creation
echo "=== Example 2: Manual Cookie Creation ===\n";

$jar = new CookieJar;

// Create cookies manually
$jar->add(new Cookie('session_id', 'xyz789', domain: 'example.com', path: '/'));
$jar->add(new Cookie('user_pref', 'dark_mode', domain: 'example.com', path: '/app'));

echo "Created {$jar->count()} cookies manually\n";

// Get cookies for specific domain/path
$cookieHeader = $jar->getCookieHeader('example.com', '/app');
echo "Cookie header for example.com/app: {$cookieHeader}\n\n";

// Example 3: Cookie Attributes
echo "=== Example 3: Cookie Attributes ===\n";

$cookie = new Cookie(
    name: 'secure_token',
    value: 'secret123',
    expires: time() + 3600,    // Expires in 1 hour
    path: '/api',               // Only for /api paths
    domain: 'api.example.com',  // Only for api subdomain
    secure: true,               // Only over HTTPS
    httpOnly: true              // Not accessible via JavaScript
);

echo 'Cookie Name: '.$cookie->getName()."\n";
echo 'Cookie Value: '.$cookie->getValue()."\n";
echo 'Expires: '.date('Y-m-d H:i:s', $cookie->getExpires())."\n";
echo 'Path: '.$cookie->getPath()."\n";
echo 'Domain: '.$cookie->getDomain()."\n";
echo 'Secure: '.($cookie->isSecure() ? 'Yes' : 'No')."\n";
echo 'HttpOnly: '.($cookie->isHttpOnly() ? 'Yes' : 'No')."\n\n";

// Example 4: Parsing Set-Cookie Headers
echo "=== Example 4: Parsing Set-Cookie Headers ===\n";

$setCookieHeader = 'session=abc123; Path=/; Domain=example.com; Expires=Wed, 21 Oct 2025 07:28:00 GMT; Secure; HttpOnly';
$cookie = Cookie::fromSetCookieHeader($setCookieHeader);

echo "Parsed Set-Cookie header:\n";
echo '- Name: '.$cookie->getName()."\n";
echo '- Value: '.$cookie->getValue()."\n";
echo '- Path: '.$cookie->getPath()."\n";
echo '- Domain: '.$cookie->getDomain()."\n";
echo '- Secure: '.($cookie->isSecure() ? 'Yes' : 'No')."\n";
echo '- HttpOnly: '.($cookie->isHttpOnly() ? 'Yes' : 'No')."\n\n";

// Example 5: Cookie Expiration
echo "=== Example 5: Cookie Expiration ===\n";

$jar = new CookieJar;

// Add permanent cookie (no expiration)
$jar->add(new Cookie('permanent', 'value1'));
echo "Added permanent cookie (no expiration)\n";

// Add expiring cookie
$jar->add(new Cookie('expiring', 'value2', expires: time() + 10));
echo "Added cookie that expires in 10 seconds\n";

// Add expired cookie
$jar->add(new Cookie('already_expired', 'value3', expires: time() - 3600));
echo "Added already-expired cookie\n";

echo "\nCurrent cookie count: ".$jar->count()."\n";

// Get cookies for request (expired ones are filtered out)
$cookies = $jar->getMatchingCookies('example.com', '/');
echo 'Valid cookies for request: '.count($cookies)."\n";

// Remove expired cookies from jar
$jar->removeExpired();
echo 'After removing expired: '.$jar->count()." cookies\n\n";

// Example 6: Domain and Path Matching
echo "=== Example 6: Domain and Path Matching ===\n";

$jar = new CookieJar;

$jar->add(new Cookie('cookie1', 'for-example.com', domain: 'example.com', path: '/'));
$jar->add(new Cookie('cookie2', 'for-app-path', domain: 'example.com', path: '/app'));
$jar->add(new Cookie('cookie3', 'for-api-path', domain: 'example.com', path: '/api'));
$jar->add(new Cookie('cookie4', 'for-other.com', domain: 'other.com', path: '/'));

echo "Cookies for example.com / (root path):\n";
$header = $jar->getCookieHeader('example.com', '/');
echo "  {$header}\n\n";

echo "Cookies for example.com/app:\n";
$header = $jar->getCookieHeader('example.com', '/app');
echo "  {$header}\n\n";

echo "Cookies for example.com/api:\n";
$header = $jar->getCookieHeader('example.com', '/api');
echo "  {$header}\n\n";

echo "Cookies for other.com:\n";
$header = $jar->getCookieHeader('other.com', '/');
echo "  {$header}\n\n";

// Example 7: Session Persistence
echo "=== Example 7: Session Persistence ===\n";

$jar = new CookieJar;
$client = new Curl(new None, 'https://httpbin.org');
$client->addMiddleware(new CookieMiddleware($jar));

echo "Simulating login flow:\n";

// Step 1: Login request (server sets session cookie)
echo "1. POST /login (server sets session cookie)\n";
$response = $client->get('/cookies/set?session_id=user123&logged_in=true');

// Step 2: Subsequent requests automatically include the cookie
echo "2. GET /dashboard (session cookie sent automatically)\n";
$response = $client->get('/cookies');

echo "3. GET /profile (session cookie sent automatically)\n";
$response = $client->get('/cookies');

echo "\nAll requests after login automatically include the session cookie!\n";
echo 'Cookies in jar: '.$jar->count()."\n\n";

// Example 8: Cookie Jar Manipulation
echo "=== Example 8: Cookie Jar Manipulation ===\n";

$jar = new CookieJar;

// Add multiple cookies
$jar->add(new Cookie('cookie1', 'value1'));
$jar->add(new Cookie('cookie2', 'value2'));
$jar->add(new Cookie('cookie3', 'value3'));

echo "Added 3 cookies\n";
echo 'Count: '.$jar->count()."\n";

// Get all cookies
$allCookies = $jar->all();
echo "\nAll cookies:\n";
foreach ($allCookies as $cookie) {
    echo "- {$cookie->getName()} = {$cookie->getValue()}\n";
}

// Clear all cookies
$jar->clear();
echo "\nAfter clear: ".$jar->count()." cookies\n\n";

// Example 9: Automatic Cookie Management with Real API
echo "=== Example 9: Automatic Cookie Management ===\n";

$jar = new CookieJar;
$client = new Curl(new None, 'https://httpbin.org');
$cookieMiddleware = new CookieMiddleware($jar);
$client->addMiddleware($cookieMiddleware);

echo "The CookieMiddleware automatically:\n";
echo "1. Stores cookies from Set-Cookie headers\n";
echo "2. Sends matching cookies with each request\n";
echo "3. Handles domain and path matching\n";
echo "4. Filters out expired cookies\n\n";

echo "Making request to set cookies...\n";
$client->get('/cookies/set?auto_managed=true&feature=enabled');

echo "Cookies are now stored in the jar\n";
echo "Subsequent requests will include these cookies automatically\n\n";

// Example 10: Best Practices
echo "=== Example 10: Cookie Best Practices ===\n";
echo "
1. SECURITY:
   - Use 'Secure' flag for HTTPS-only cookies
   - Use 'HttpOnly' flag to prevent JavaScript access
   - Set appropriate Domain to prevent cookie leakage
   - Don't store sensitive data in cookies

2. EXPIRATION:
   - Session cookies: No expiration (deleted when browser closes)
   - Remember me: 7-30 days
   - Tracking: 1-2 years
   - Security tokens: Short-lived (minutes to hours)

3. DOMAIN & PATH:
   - Use specific domains to limit cookie scope
   - Use paths to separate different app sections
   - Be careful with subdomain cookies

4. SIZE LIMITS:
   - Maximum ~4KB per cookie
   - Maximum ~50 cookies per domain
   - Keep cookies small and focused

5. DEBUGGING:
   - Check cookie count: \$jar->count()
   - List all cookies: \$jar->all()
   - Clear cookies: \$jar->clear()
   - Remove expired: \$jar->removeExpired()

6. COMMON PATTERNS:
   - Authentication: Store session ID in cookie
   - Preferences: Store UI settings
   - Tracking: Analytics and user tracking
   - CSRF Protection: Anti-CSRF tokens
";

echo "Cookie examples complete!\n";
