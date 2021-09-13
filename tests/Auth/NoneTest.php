<?php

namespace Auth;

use PHPUnit\Framework\TestCase;
use Villaflor\Connection\Auth\None;

class NoneTest extends TestCase
{
    public function testGetHeaders()
    {
        $auth = new None();
        $headers = $auth->getHeaders();

        $this->assertEquals([], $headers);
    }
}
