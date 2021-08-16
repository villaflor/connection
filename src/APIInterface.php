<?php

namespace Villaflor\Connection;

use Villaflor\Connection\Adapter\AdapterInterface;

interface APIInterface
{
    public function __construct(AdapterInterface $adapter);
}