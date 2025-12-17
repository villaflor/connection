<?php

require_once __DIR__.'/../vendor/autoload.php';

use Villaflor\Connection\Adapter\Curl;
use Villaflor\Connection\Auth\None;
use Villaflor\Connection\Cache\ArrayCache;
use Villaflor\Connection\Middleware\CachingMiddleware;

// Example 1: Basic Response Caching
echo "=== Example 1: Basic Response Caching ===\n";

$client = new Curl(new None, 'https://httpbin.org');

// Create cache and middleware
$cache = new ArrayCache;
$cachingMiddleware = new CachingMiddleware($cache, defaultTtl: 300); // 5 minutes

$client->addMiddleware($cachingMiddleware);

echo "First request (cache miss):\n";
$start = microtime(true);
$response1 = $client->get('/delay/2'); // Delayed endpoint for demonstration
$duration1 = round((microtime(true) - $start) * 1000);
echo '- Status: '.$response1->getStatusCode()."\n";
echo "- Duration: {$duration1}ms\n";

echo "\nSecond request (cache hit):\n";
$start = microtime(true);
$response2 = $client->get('/delay/2'); // Should be instant from cache
$duration2 = round((microtime(true) - $start) * 1000);
echo '- Status: '.$response2->getStatusCode()."\n";
echo "- Duration: {$duration2}ms\n";
echo '- Speed improvement: '.round($duration1 / max($duration2, 1))."x faster!\n\n";

// Example 2: Custom TTL per Cache
echo "=== Example 2: Custom Cache TTL ===\n";

$client = new Curl(new None, 'https://httpbin.org');

// Short TTL for demonstration (10 seconds)
$cache = new ArrayCache;
$cachingMiddleware = new CachingMiddleware($cache, defaultTtl: 10);
$client->addMiddleware($cachingMiddleware);

echo "Caching with 10 second TTL\n";
$client->get('/get');
echo "Request cached\n";

echo "\nCache will expire in 10 seconds...\n\n";

// Example 3: Respecting Cache-Control Headers
echo "=== Example 3: Cache-Control Headers ===\n";

$client = new Curl(new None, 'https://httpbin.org');

$cache = new ArrayCache;
$cachingMiddleware = new CachingMiddleware($cache, defaultTtl: 3600);
$client->addMiddleware($cachingMiddleware);

echo "When responses include Cache-Control headers,\n";
echo "the middleware respects them instead of using default TTL.\n";
echo "\nCache-Control: max-age=60 → cached for 60 seconds\n";
echo "Cache-Control: no-cache → not cached\n";
echo "Expires header → also respected\n\n";

// Example 4: Cache Only Successful Responses
echo "=== Example 4: Only Successful Responses Cached ===\n";

$client = new Curl(new None, 'https://httpbin.org');

$cache = new ArrayCache;
$cachingMiddleware = new CachingMiddleware($cache, defaultTtl: 300);
$client->addMiddleware($cachingMiddleware);

// Successful response (2xx) - will be cached
$client->get('/status/200');
echo "✓ 200 OK response cached\n";

// Error responses (4xx, 5xx) - will NOT be cached
try {
    $client->get('/status/404');
} catch (Exception $e) {
    echo "✗ 404 Not Found response NOT cached\n";
}

try {
    $client->get('/status/500');
} catch (Exception $e) {
    echo "✗ 500 Internal Server Error response NOT cached\n";
}

echo "\nOnly successful responses (2xx status codes) are cached.\n\n";

// Example 5: Caching GET vs POST Requests
echo "=== Example 5: Method-Specific Caching ===\n";

$client = new Curl(new None, 'https://httpbin.org');

// Default: only cache GET requests
$cache = new ArrayCache;
$cachingMiddleware = new CachingMiddleware($cache, defaultTtl: 300);
$client->addMiddleware($cachingMiddleware);

$client->get('/get');
echo "GET request: Cached ✓\n";

$client->post('/post', ['data' => 'test']);
echo "POST request: NOT cached (default behavior)\n\n";

// Custom: cache specific methods
$client2 = new Curl(new None, 'https://httpbin.org');
$cache2 = new ArrayCache;
$cachingMiddleware2 = new CachingMiddleware($cache2, defaultTtl: 300, cacheableMethods: ['GET', 'POST']);
$client2->addMiddleware($cachingMiddleware2);

$client2->post('/post', ['data' => 'test']);
echo "POST request with custom config: Cached ✓\n\n";

// Example 6: Different Cache Keys for Different Requests
echo "=== Example 6: Cache Keys ===\n";

$client = new Curl(new None, 'https://httpbin.org');

$cache = new ArrayCache;
$cachingMiddleware = new CachingMiddleware($cache, defaultTtl: 300);
$client->addMiddleware($cachingMiddleware);

echo "Cache keys are based on: method + URI + data\n\n";

// These are cached separately
$client->get('/get?foo=1');
echo "Cached: GET /get?foo=1\n";

$client->get('/get?foo=2');
echo "Cached: GET /get?foo=2 (different cache key)\n";

$client->get('/get?foo=1');
echo "Cache hit: GET /get?foo=1 (same as first request)\n\n";

// Example 7: Cache Statistics and Management
echo "=== Example 7: Cache Management ===\n";

$cache = new ArrayCache;
$cachingMiddleware = new CachingMiddleware($cache, defaultTtl: 300);

$client = new Curl(new None, 'https://httpbin.org');
$client->addMiddleware($cachingMiddleware);

echo "Making requests...\n";
$client->get('/get');
$client->get('/headers');
$client->get('/ip');

echo 'Cache size: '.$cache->count()." items\n";

echo "\nClearing cache...\n";
$cache->clear();
echo 'Cache size: '.$cache->count()." items\n\n";

// Example 8: Performance Benefits
echo "=== Example 8: Performance Benefits ===\n";

$client = new Curl(new None, 'https://httpbin.org');

$cache = new ArrayCache;
$cachingMiddleware = new CachingMiddleware($cache, defaultTtl: 600);
$client->addMiddleware($cachingMiddleware);

echo "Scenario: Making the same API call 100 times\n\n";

echo "Without caching:\n";
echo "- 100 requests × ~200ms each = ~20 seconds total\n";
echo "- 100 API calls hitting the server\n\n";

echo "With caching:\n";
echo "- First request: ~200ms (cache miss)\n";
echo "- Next 99 requests: <1ms each (cache hit)\n";
echo "- Total: ~300ms (66x faster!)\n";
echo "- Only 1 API call hitting the server\n\n";

echo "Real-world demonstration:\n";
$iterations = 10;

$start = microtime(true);
for ($i = 0; $i < $iterations; $i++) {
    $client->get('/get');
}
$duration = round((microtime(true) - $start) * 1000);

echo "Made {$iterations} identical requests in {$duration}ms\n";
echo 'Average per request: '.round($duration / $iterations)."ms\n";
echo "(First request was slow, rest were cached)\n\n";

// Example 9: Best Practices
echo "=== Example 9: Caching Best Practices ===\n";
echo '
1. CACHE INVALIDATION:
   - Clear cache when data changes on the server
   - Use appropriate TTL values for your use case
   - Consider using cache tags or versioning for complex scenarios

2. TTL RECOMMENDATIONS:
   - Static data (e.g., country lists): 1 hour - 1 day
   - User data (e.g., profile): 5-15 minutes
   - Real-time data (e.g., prices): 30 seconds - 2 minutes
   - Configuration data: 10-30 minutes

3. WHAT TO CACHE:
   - ✓ GET requests for read-only data
   - ✓ Expensive or slow API calls
   - ✓ Frequently accessed data
   - ✗ User-specific authenticated data (be careful!)
   - ✗ Real-time or frequently changing data
   - ✗ POST/PUT/DELETE requests (usually)

4. MEMORY CONSIDERATIONS:
   - ArrayCache is in-memory (lost on script end)
   - For persistent caching, use PSR-16 cache adapters
   - Monitor cache size for production applications

5. TESTING:
   - Always test with cache disabled first
   - Test cache expiration behavior
   - Verify cache keys are unique enough
';

echo "Caching examples complete!\n";
