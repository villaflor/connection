<?php

namespace Villaflor\Connection\Adapter;

use Psr\Http\Message\ResponseInterface;
use Villaflor\Connection\Auth\AuthInterface;

interface AdapterInterface
{
    public function __construct(AuthInterface $auth, string $baseURI);

    /**
     * Sends a GET request.
     * Per Robustness Principle - not including the ability to send a body with a GET request (though possible in the
     * RFCs, it is never useful).
     */
    public function get(string $uri, array $data = [], array $headers = []): ResponseInterface;

    public function post(string $uri, array $data = [], array $headers = []): ResponseInterface;

    public function put(string $uri, array $data = [], array $headers = []): ResponseInterface;

    public function patch(string $uri, array $data = [], array $headers = []): ResponseInterface;

    public function delete(string $uri, array $data = [], array $headers = []): ResponseInterface;
}
