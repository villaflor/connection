<?php

namespace Villaflor\Connection\Adapter;

use InvalidArgumentException;
use Psr\Http\Message\ResponseInterface;
use Villaflor\Connection\Auth\AuthInterface;
use Villaflor\Connection\Exception\ConnectionException;
use Villaflor\Connection\Exception\InvalidResponseException;
use Villaflor\Connection\Exception\ResponseException;
use Villaflor\Connection\Exception\TimeoutException;
use Villaflor\Connection\Http\Response;
use Villaflor\Connection\Http\Stream;
use Villaflor\Connection\Middleware\MiddlewareInterface;
use Villaflor\Connection\Retry\RetryConfig;

/**
 * HTTP client adapter using native PHP cURL extension.
 *
 * This adapter provides a PSR-7 compliant HTTP client implementation using
 * PHP's cURL extension. It supports all standard HTTP methods, custom headers,
 * configurable timeouts, and automatic error handling.
 */
class Curl implements AdapterInterface
{
    private readonly string $baseUri;

    private readonly array $defaultHeaders;

    private int $timeout = 30;

    private int $connectTimeout = 10;

    private ?RetryConfig $retryConfig = null;

    /** @var array<MiddlewareInterface> */
    private array $middleware = [];

    private ?string $proxy = null;

    private ?string $proxyAuth = null;

    private bool $verifyPeer = true;

    private bool $verifyHost = true;

    private ?string $caBundle = null;

    private ?string $sslCert = null;

    private ?string $sslKey = null;

    /**
     * Create a new Curl adapter instance.
     *
     * @param  AuthInterface  $auth  The authentication strategy to use
     * @param  string  $baseURI  The base URI for all requests
     */
    public function __construct(AuthInterface $auth, string $baseURI)
    {
        $this->baseUri = rtrim($baseURI, '/');
        $this->defaultHeaders = array_merge(
            $auth->getHeaders(),
            ['Accept' => 'application/json']
        );
    }

    /**
     * Send a GET HTTP request.
     *
     * @param  string  $uri  The request URI (absolute or relative to base URI)
     * @param  array  $data  Query parameters to append to the URI
     * @param  array  $headers  Additional headers to send with the request
     * @return ResponseInterface The PSR-7 response
     *
     * @throws ResponseException If the request fails or returns 4xx/5xx status
     */
    public function get(string $uri, array $data = [], array $headers = []): ResponseInterface
    {
        return $this->request('GET', $uri, $data, $headers);
    }

    /**
     * Send a POST HTTP request.
     *
     * @param  string  $uri  The request URI (absolute or relative to base URI)
     * @param  array  $data  Data to send as JSON body, or form_params for form data
     * @param  array  $headers  Additional headers to send with the request
     * @return ResponseInterface The PSR-7 response
     *
     * @throws ResponseException If the request fails or returns 4xx/5xx status
     */
    public function post(string $uri, array $data = [], array $headers = []): ResponseInterface
    {
        return $this->request('POST', $uri, $data, $headers);
    }

    /**
     * Send a PUT HTTP request.
     *
     * @param  string  $uri  The request URI (absolute or relative to base URI)
     * @param  array  $data  Data to send as JSON body
     * @param  array  $headers  Additional headers to send with the request
     * @return ResponseInterface The PSR-7 response
     *
     * @throws ResponseException If the request fails or returns 4xx/5xx status
     */
    public function put(string $uri, array $data = [], array $headers = []): ResponseInterface
    {
        return $this->request('PUT', $uri, $data, $headers);
    }

    /**
     * Send a PATCH HTTP request.
     *
     * @param  string  $uri  The request URI (absolute or relative to base URI)
     * @param  array  $data  Data to send as JSON body
     * @param  array  $headers  Additional headers to send with the request
     * @return ResponseInterface The PSR-7 response
     *
     * @throws ResponseException If the request fails or returns 4xx/5xx status
     */
    public function patch(string $uri, array $data = [], array $headers = []): ResponseInterface
    {
        return $this->request('PATCH', $uri, $data, $headers);
    }

    /**
     * Send a DELETE HTTP request.
     *
     * @param  string  $uri  The request URI (absolute or relative to base URI)
     * @param  array  $data  Data to send as JSON body (optional)
     * @param  array  $headers  Additional headers to send with the request
     * @return ResponseInterface The PSR-7 response
     *
     * @throws ResponseException If the request fails or returns 4xx/5xx status
     */
    public function delete(string $uri, array $data = [], array $headers = []): ResponseInterface
    {
        return $this->request('DELETE', $uri, $data, $headers);
    }

    /**
     * Set the request timeout in seconds.
     *
     * @param  int  $timeout  Maximum time for the request to complete (default: 30)
     */
    public function setTimeout(int $timeout): void
    {
        $this->timeout = $timeout;
    }

    /**
     * Set the connection timeout in seconds.
     *
     * @param  int  $connectTimeout  Maximum time to establish connection (default: 10)
     */
    public function setConnectTimeout(int $connectTimeout): void
    {
        $this->connectTimeout = $connectTimeout;
    }

    /**
     * Enable retry logic with the given configuration.
     *
     * @param  RetryConfig  $config  The retry configuration
     */
    public function setRetryConfig(RetryConfig $config): void
    {
        $this->retryConfig = $config;
    }

    /**
     * Add middleware to the request/response processing pipeline.
     *
     * Middleware is executed in the order it is added. Each middleware can:
     * - Modify the request before it is sent
     * - Modify the response after it is received
     * - Handle errors during the request/response cycle
     *
     * @param  MiddlewareInterface  $middleware  The middleware to add
     */
    public function addMiddleware(MiddlewareInterface $middleware): void
    {
        $this->middleware[] = $middleware;
    }

    /**
     * Set proxy configuration.
     *
     * @param  string  $proxy  Proxy URL (e.g., 'http://proxy.example.com:8080')
     * @param  string|null  $auth  Proxy authentication in format 'username:password'
     */
    public function setProxy(string $proxy, ?string $auth = null): void
    {
        $this->proxy = $proxy;
        $this->proxyAuth = $auth;
    }

    /**
     * Enable or disable SSL peer verification.
     *
     * @param  bool  $verify  Whether to verify the peer's SSL certificate
     */
    public function setVerifyPeer(bool $verify): void
    {
        $this->verifyPeer = $verify;
    }

    /**
     * Enable or disable SSL host verification.
     *
     * @param  bool  $verify  Whether to verify the certificate's name against host
     */
    public function setVerifyHost(bool $verify): void
    {
        $this->verifyHost = $verify;
    }

    /**
     * Set custom CA bundle for SSL verification.
     *
     * @param  string  $path  Path to the CA bundle file
     */
    public function setCaBundle(string $path): void
    {
        $this->caBundle = $path;
    }

    /**
     * Set client SSL certificate.
     *
     * @param  string  $cert  Path to the client certificate file
     * @param  string|null  $key  Path to the private key file (if separate)
     */
    public function setSslCert(string $cert, ?string $key = null): void
    {
        $this->sslCert = $cert;
        $this->sslKey = $key;
    }

    /**
     * Send an HTTP request with the specified method.
     *
     * @param  string  $method  HTTP method (GET, POST, PUT, PATCH, DELETE)
     * @param  string  $uri  The request URI (absolute or relative to base URI)
     * @param  array  $data  Request data (query params for GET, body for others)
     * @param  array  $headers  Additional headers to send with the request
     * @return ResponseInterface The PSR-7 response
     *
     * @throws InvalidArgumentException If the HTTP method is invalid
     * @throws ResponseException If the request fails or returns 4xx/5xx status
     */
    public function request(string $method, string $uri, array $data = [], array $headers = []): ResponseInterface
    {
        $method = strtoupper($method);

        if (! in_array($method, ['GET', 'POST', 'PUT', 'PATCH', 'DELETE'])) {
            throw new InvalidArgumentException('Request method must be GET, POST, PUT, PATCH, or DELETE');
        }

        // Process through middleware chain
        if (! empty($this->middleware)) {
            return $this->processMiddleware($method, $uri, $data, $headers);
        }

        // No middleware, execute directly with retry logic
        return $this->executeRequestWithRetry($method, $uri, $data, $headers);
    }

    /**
     * Process the request through the middleware chain.
     *
     * @param  string  $method  HTTP method
     * @param  string  $uri  Request URI
     * @param  array  $data  Request data
     * @param  array  $headers  Request headers
     * @return ResponseInterface The response
     */
    private function processMiddleware(string $method, string $uri, array $data, array $headers): ResponseInterface
    {
        $pipeline = array_reduce(
            array_reverse($this->middleware),
            function ($next, $middleware) {
                return function ($method, $uri, $data, $headers) use ($middleware, $next) {
                    return $middleware->handle($method, $uri, $data, $headers, $next);
                };
            },
            fn ($method, $uri, $data, $headers) => $this->executeRequestWithRetry($method, $uri, $data, $headers)
        );

        return $pipeline($method, $uri, $data, $headers);
    }

    /**
     * Execute request with retry logic.
     *
     * @param  string  $method  HTTP method
     * @param  string  $uri  Request URI
     * @param  array  $data  Request data
     * @param  array  $headers  Request headers
     * @return ResponseInterface The response
     *
     * @throws ResponseException If the request fails after all retries
     * @throws ConnectionException If the request fails at network level
     */
    private function executeRequestWithRetry(string $method, string $uri, array $data, array $headers): ResponseInterface
    {
        $maxAttempts = $this->retryConfig?->getMaxAttempts() ?? 1;
        $attempt = 0;
        $lastException = null;

        while ($attempt < $maxAttempts) {
            $attempt++;

            try {
                return $this->executeRequest($method, $uri, $data, $headers);
            } catch (ResponseException $e) {
                $lastException = $e;

                // Check if we should retry this status code
                if ($this->retryConfig && $this->retryConfig->shouldRetry($e->getCode())) {
                    // If we haven't exhausted our attempts, wait and retry
                    if ($attempt < $maxAttempts) {
                        $delay = $this->retryConfig->getDelay($attempt);
                        usleep($delay * 1000); // Convert milliseconds to microseconds

                        continue;
                    }
                }

                // Either not retryable or we've exhausted our attempts
                throw $e;
            }
        }

        // @codeCoverageIgnoreStart
        // This should never be reached, but just in case
        throw $lastException ?? new ConnectionException('Request failed after all retry attempts');
        // @codeCoverageIgnoreEnd
    }

    /**
     * Execute a single HTTP request attempt.
     *
     * @param  string  $method  HTTP method
     * @param  string  $uri  The request URI
     * @param  array  $data  Request data
     * @param  array  $headers  Additional headers
     * @return ResponseInterface The PSR-7 response
     *
     * @throws ConnectionException If the request fails at network level
     * @throws TimeoutException If the request times out
     * @throws ResponseException If the response has 4xx/5xx status
     */
    private function executeRequest(string $method, string $uri, array $data, array $headers): ResponseInterface
    {
        $url = $this->buildUrl($uri, $method === 'GET' ? $data : []);
        $ch = curl_init($url);

        $this->configureCurl($ch, $method, $data, $headers);

        $responseHeaders = [];
        $response = curl_exec($ch);

        if ($response === false) {
            $error = curl_error($ch);
            $errno = curl_errno($ch);
            curl_close($ch);

            // Throw specific exceptions based on error type
            if (in_array($errno, [CURLE_OPERATION_TIMEDOUT, CURLE_COULDNT_CONNECT])) {
                throw new TimeoutException("Request timed out: {$error}", $errno);
            }

            throw new ConnectionException("cURL error ({$errno}): {$error}", $errno);
        }

        $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $headerContent = substr($response, 0, $headerSize);
        $body = substr($response, $headerSize);

        curl_close($ch);

        $responseHeaders = $this->parseHeaders($headerContent);

        $responseObject = new Response(
            $statusCode,
            $responseHeaders,
            new Stream($body),
            $this->getReasonPhrase($statusCode)
        );

        if ($statusCode >= 400) {
            throw ResponseException::fromResponse($responseObject);
        }

        return $responseObject;
    }

    private function buildUrl(string $uri, array $queryParams = []): string
    {
        // Handle absolute URLs
        if (str_starts_with($uri, 'http://') || str_starts_with($uri, 'https://')) {
            $url = $uri;
        } else {
            $url = $this->baseUri.'/'.ltrim($uri, '/');
        }

        if (! empty($queryParams)) {
            $separator = str_contains($url, '?') ? '&' : '?';
            $url .= $separator.http_build_query($queryParams);
        }

        return $url;
    }

    private function configureCurl($ch, string $method, array $data, array $headers): void
    {
        $allHeaders = array_merge($this->defaultHeaders, $headers);

        // Handle different data formats
        $hasFormParams = isset($data['form_params']);
        $hasMultipart = isset($data['multipart']);

        if ($hasMultipart) {
            // Handle multipart/form-data (file uploads)
            $multipartData = $this->buildMultipartData($data['multipart']);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $multipartData);
            // Don't set Content-Type header - cURL will set it automatically with boundary
            unset($allHeaders['Content-Type']);
        } elseif ($method !== 'GET' && ! empty($data) && ! $hasFormParams) {
            // JSON body
            $body = json_encode($data);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
            $allHeaders['Content-Type'] = 'application/json';
        } elseif ($hasFormParams) {
            // URL-encoded form data
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data['form_params']));
            $allHeaders['Content-Type'] = 'application/x-www-form-urlencoded';
        }

        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, $this->timeout);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $this->connectTimeout);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_MAXREDIRS, 5);

        // SSL Configuration
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, $this->verifyPeer);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, $this->verifyHost ? 2 : 0);

        if ($this->caBundle !== null) {
            curl_setopt($ch, CURLOPT_CAINFO, $this->caBundle);
        }

        // @codeCoverageIgnoreStart
        if ($this->sslCert !== null) {
            curl_setopt($ch, CURLOPT_SSLCERT, $this->sslCert);
            if ($this->sslKey !== null) {
                curl_setopt($ch, CURLOPT_SSLKEY, $this->sslKey);
            }
        }
        // @codeCoverageIgnoreEnd

        // Proxy Configuration
        // @codeCoverageIgnoreStart
        if ($this->proxy !== null) {
            curl_setopt($ch, CURLOPT_PROXY, $this->proxy);
            if ($this->proxyAuth !== null) {
                curl_setopt($ch, CURLOPT_PROXYUSERPWD, $this->proxyAuth);
            }
        }
        // @codeCoverageIgnoreEnd

        // Format headers for cURL
        $formattedHeaders = [];
        foreach ($allHeaders as $name => $value) {
            $formattedHeaders[] = "{$name}: {$value}";
        }
        curl_setopt($ch, CURLOPT_HTTPHEADER, $formattedHeaders);
    }

    /**
     * Build multipart form data for file uploads.
     *
     * @param  array  $multipart  Array of multipart fields
     * @return array Formatted multipart data for cURL
     */
    private function buildMultipartData(array $multipart): array
    {
        $data = [];

        foreach ($multipart as $field) {
            $name = $field['name'] ?? null;
            $contents = $field['contents'] ?? null;

            if ($name === null || $contents === null) {
                continue;
            }

            // Handle file uploads
            if (isset($field['filename'])) {
                // If contents is a file path and the file exists, use CURLFile
                if (is_string($contents) && file_exists($contents)) {
                    $mimeType = $field['headers']['Content-Type'] ?? mime_content_type($contents);
                    $filename = $field['filename'];
                    $data[$name] = new \CURLFile($contents, $mimeType, $filename);
                } else {
                    // Contents is raw file data, save to temp file in system tmp directory
                    $tmpPath = tempnam(sys_get_temp_dir(), 'curl_upload_');
                    file_put_contents($tmpPath, $contents);

                    $mimeType = $field['headers']['Content-Type'] ?? 'application/octet-stream';
                    $filename = $field['filename'];
                    $data[$name] = new \CURLFile($tmpPath, $mimeType, $filename);

                    // Register cleanup for the temp file
                    $this->registerTempFileCleanup($tmpPath);
                }
            } else {
                // Regular form field
                $data[$name] = $contents;
            }
        }

        return $data;
    }

    /**
     * Register a temporary file for cleanup on shutdown.
     *
     * @param  string  $tmpPath  Path to the temporary file
     */
    private function registerTempFileCleanup(string $tmpPath): void
    {
        // @codeCoverageIgnoreStart
        register_shutdown_function(function () use ($tmpPath) {
            if (file_exists($tmpPath)) {
                @unlink($tmpPath);
            }
        });
        // @codeCoverageIgnoreEnd
    }

    private function parseHeaders(string $headerContent): array
    {
        $headers = [];
        $lines = explode("\r\n", $headerContent);

        foreach ($lines as $line) {
            if (str_contains($line, ':')) {
                [$name, $value] = explode(':', $line, 2);
                $name = trim($name);
                $value = trim($value);

                if (isset($headers[$name])) {
                    $headers[$name][] = $value;
                } else {
                    $headers[$name] = [$value];
                }
            }
        }

        return $headers;
    }

    private function getReasonPhrase(int $statusCode): string
    {
        $phrases = [
            200 => 'OK',
            201 => 'Created',
            202 => 'Accepted',
            204 => 'No Content',
            301 => 'Moved Permanently',
            302 => 'Found',
            304 => 'Not Modified',
            400 => 'Bad Request',
            401 => 'Unauthorized',
            403 => 'Forbidden',
            404 => 'Not Found',
            405 => 'Method Not Allowed',
            422 => 'Unprocessable Entity',
            500 => 'Internal Server Error',
            502 => 'Bad Gateway',
            503 => 'Service Unavailable',
        ];

        return $phrases[$statusCode] ?? 'Unknown';
    }

    /**
     * Send a GET request and automatically decode JSON response.
     *
     * @param  string  $uri  The request URI (absolute or relative to base URI)
     * @param  array  $data  Query parameters to append to the URI
     * @param  array  $headers  Additional headers to send with the request
     * @return mixed The decoded JSON response
     *
     * @throws ResponseException If the request fails or returns 4xx/5xx status
     * @throws InvalidResponseException If the response cannot be decoded as JSON
     */
    public function getJson(string $uri, array $data = [], array $headers = []): mixed
    {
        $response = $this->get($uri, $data, $headers);

        return $this->decodeJsonResponse($response);
    }

    /**
     * Send a POST request with JSON body and automatically decode JSON response.
     *
     * @param  string  $uri  The request URI (absolute or relative to base URI)
     * @param  array  $data  Data to send as JSON body
     * @param  array  $headers  Additional headers to send with the request
     * @return mixed The decoded JSON response
     *
     * @throws ResponseException If the request fails or returns 4xx/5xx status
     * @throws InvalidResponseException If the response cannot be decoded as JSON
     */
    public function postJson(string $uri, array $data = [], array $headers = []): mixed
    {
        $response = $this->post($uri, $data, $headers);

        return $this->decodeJsonResponse($response);
    }

    /**
     * Send a PUT request with JSON body and automatically decode JSON response.
     *
     * @param  string  $uri  The request URI (absolute or relative to base URI)
     * @param  array  $data  Data to send as JSON body
     * @param  array  $headers  Additional headers to send with the request
     * @return mixed The decoded JSON response
     *
     * @throws ResponseException If the request fails or returns 4xx/5xx status
     * @throws InvalidResponseException If the response cannot be decoded as JSON
     */
    public function putJson(string $uri, array $data = [], array $headers = []): mixed
    {
        $response = $this->put($uri, $data, $headers);

        return $this->decodeJsonResponse($response);
    }

    /**
     * Send a PATCH request with JSON body and automatically decode JSON response.
     *
     * @param  string  $uri  The request URI (absolute or relative to base URI)
     * @param  array  $data  Data to send as JSON body
     * @param  array  $headers  Additional headers to send with the request
     * @return mixed The decoded JSON response
     *
     * @throws ResponseException If the request fails or returns 4xx/5xx status
     * @throws InvalidResponseException If the response cannot be decoded as JSON
     */
    public function patchJson(string $uri, array $data = [], array $headers = []): mixed
    {
        $response = $this->patch($uri, $data, $headers);

        return $this->decodeJsonResponse($response);
    }

    /**
     * Send a DELETE request and automatically decode JSON response.
     *
     * @param  string  $uri  The request URI (absolute or relative to base URI)
     * @param  array  $data  Data to send as JSON body (optional)
     * @param  array  $headers  Additional headers to send with the request
     * @return mixed The decoded JSON response
     *
     * @throws ResponseException If the request fails or returns 4xx/5xx status
     * @throws InvalidResponseException If the response cannot be decoded as JSON
     */
    public function deleteJson(string $uri, array $data = [], array $headers = []): mixed
    {
        $response = $this->delete($uri, $data, $headers);

        return $this->decodeJsonResponse($response);
    }

    /**
     * Decode a JSON response body.
     *
     * @param  ResponseInterface  $response  The HTTP response
     * @return mixed The decoded JSON data
     *
     * @throws InvalidResponseException If the response cannot be decoded as JSON
     */
    private function decodeJsonResponse(ResponseInterface $response): mixed
    {
        $body = (string) $response->getBody();

        if (empty($body)) {
            return null;
        }

        $decoded = json_decode($body, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new InvalidResponseException(
                'Failed to decode JSON response: '.json_last_error_msg()
            );
        }

        return $decoded;
    }
}
