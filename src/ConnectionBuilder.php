<?php

namespace Villaflor\Connection;

use Villaflor\Connection\Adapter\Curl;
use Villaflor\Connection\Auth\APIKey;
use Villaflor\Connection\Auth\APIToken;
use Villaflor\Connection\Auth\AuthInterface;
use Villaflor\Connection\Auth\CustomHeaders;
use Villaflor\Connection\Auth\GoogleServiceAccount;
use Villaflor\Connection\Auth\None;
use Villaflor\Connection\Auth\UserServiceKey;

/**
 * Fluent builder for creating HTTP client instances.
 *
 * Provides a convenient, chainable API for configuring and creating
 * HTTP clients with various authentication methods and settings.
 *
 * @example
 * ```php
 * $client = ConnectionBuilder::create()
 *     ->withBaseUri('https://api.example.com')
 *     ->withBearerToken('your-token')
 *     ->withTimeout(60)
 *     ->build();
 * ```
 */
class ConnectionBuilder
{
    private ?string $baseUri = null;

    private ?AuthInterface $auth = null;

    private int $timeout = 30;

    private int $connectTimeout = 10;

    /**
     * Create a new connection builder instance.
     */
    public static function create(): self
    {
        return new self;
    }

    /**
     * Set the base URI for all requests.
     *
     * @param  string  $baseUri  The base URI (e.g., 'https://api.example.com')
     */
    public function withBaseUri(string $baseUri): self
    {
        $this->baseUri = $baseUri;

        return $this;
    }

    /**
     * Set a custom authentication strategy.
     *
     * @param  AuthInterface  $auth  The authentication strategy
     */
    public function withAuth(AuthInterface $auth): self
    {
        $this->auth = $auth;

        return $this;
    }

    /**
     * Use Bearer token authentication (API Token).
     *
     * @param  string  $token  The Bearer token
     */
    public function withBearerToken(string $token): self
    {
        $this->auth = new APIToken($token);

        return $this;
    }

    /**
     * Use API Key authentication.
     *
     * @param  string  $email  The email address
     * @param  string  $apiKey  The API key
     */
    public function withApiKey(string $email, string $apiKey): self
    {
        $this->auth = new APIKey($email, $apiKey);

        return $this;
    }

    /**
     * Use custom headers for authentication.
     *
     * @param  array  $headers  Custom headers to send with each request
     */
    public function withCustomHeaders(array $headers): self
    {
        $this->auth = new CustomHeaders($headers);

        return $this;
    }

    /**
     * Use User Service Key authentication.
     *
     * @param  string  $userServiceKey  The user service key
     */
    public function withUserServiceKey(string $userServiceKey): self
    {
        $this->auth = new UserServiceKey($userServiceKey);

        return $this;
    }

    /**
     * Use Google Service Account authentication.
     *
     * @param  string  $filepath  Path to the service account JSON file
     * @param  string  $apiEndpoint  The API endpoint for JWT audience
     */
    public function withGoogleServiceAccount(string $filepath, string $apiEndpoint): self
    {
        $this->auth = new GoogleServiceAccount($filepath, $apiEndpoint);

        return $this;
    }

    /**
     * Use no authentication.
     */
    public function withoutAuth(): self
    {
        $this->auth = new None;

        return $this;
    }

    /**
     * Set the request timeout in seconds.
     *
     * @param  int  $timeout  Maximum time for the request to complete
     */
    public function withTimeout(int $timeout): self
    {
        $this->timeout = $timeout;

        return $this;
    }

    /**
     * Set the connection timeout in seconds.
     *
     * @param  int  $connectTimeout  Maximum time to establish connection
     */
    public function withConnectTimeout(int $connectTimeout): self
    {
        $this->connectTimeout = $connectTimeout;

        return $this;
    }

    /**
     * Build and return the configured HTTP client.
     *
     * @return Curl The configured HTTP client instance
     *
     * @throws \InvalidArgumentException If required configuration is missing
     */
    public function build(): Curl
    {
        if ($this->baseUri === null) {
            throw new \InvalidArgumentException('Base URI is required');
        }

        if ($this->auth === null) {
            $this->auth = new None;
        }

        $client = new Curl($this->auth, $this->baseUri);
        $client->setTimeout($this->timeout);
        $client->setConnectTimeout($this->connectTimeout);

        return $client;
    }
}
