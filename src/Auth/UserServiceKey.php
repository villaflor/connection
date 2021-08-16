<?php

namespace Villaflor\Connection\Auth;

class UserServiceKey implements AuthInterface
{
    private $userServiceKey;

    public function __construct(string $userServiceKey)
    {
        $this->userServiceKey = $userServiceKey;
    }

    public function getHeaders(): array
    {
        return [
            'X-Auth-User-Service-Key' => $this->userServiceKey,
        ];
    }
}
