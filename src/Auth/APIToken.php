<?php

namespace Villaflor\Connection\Auth;

class APIToken implements AuthInterface
{
    /**
     * Create a new API Token authentication instance.
     *
     * @param  string  $apiToken  The API token to use for authentication
     */
    public function __construct(private readonly string $apiToken) {}

    public function getHeaders(): array
    {
        return [
            'Authorization' => 'Bearer '.$this->apiToken,
        ];
    }
}
