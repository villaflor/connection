<?php

namespace Villaflor\Connection\Auth;

class APIToken implements AuthInterface
{
    private string $apiToken;

    public function __construct(string $apiToken)
    {
        $this->apiToken = $apiToken;
    }

    public function getHeaders(): array
    {
        return [
            'Authorization' => 'Bearer '.$this->apiToken,
        ];
    }
}
