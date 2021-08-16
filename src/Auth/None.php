<?php

namespace Villaflor\Connection\Auth;

class None implements AuthInterface
{
    public function getHeaders(): array
    {
        return [];
    }
}
