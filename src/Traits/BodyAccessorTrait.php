<?php

namespace Villaflor\Connection\Traits;

trait BodyAccessorTrait
{
    private mixed $body;

    public function getBody()
    {
        return $this->body;
    }
}
