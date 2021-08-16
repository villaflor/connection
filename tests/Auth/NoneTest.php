<?php

namespace Auth;

use Villaflor\Connection\Auth\None;

class NoneTest extends \PHPUnit\Framework\TestCase
{
    public function testGetHeaders()
    {
        $auth    = new None();
        $headers = $auth->getHeaders();

        $this->assertEquals([], $headers);
    }
}
