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

        // Handle form_params special case
        $hasFormParams = isset($data['form_params']);

        if ($method !== 'GET' && ! empty($data) && ! $hasFormParams) {
            $body = json_encode($data);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
            $allHeaders['Content-Type'] = 'application/json';
        } elseif ($hasFormParams) {
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
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);

        // Format headers for cURL
        $formattedHeaders = [];
        foreach ($allHeaders as $name => $value) {
            $formattedHeaders[] = "{$name}: {$value}";
        }
        curl_setopt($ch, CURLOPT_HTTPHEADER, $formattedHeaders);
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
