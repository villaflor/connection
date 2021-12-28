<?php

namespace Villaflor\Connection\Auth;

class CustomHeaders implements AuthInterface
{
    private $headers;

    public function __construct(array $headers)
    {
        $this->headers = $headers;
    }

    public function getHeaders(): array
    {
        return $this->headers;
    }
}
