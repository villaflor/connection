<?php

namespace Auth;

use PHPUnit\Framework\TestCase;
use Villaflor\Connection\Auth\CustomHeaders;

class CustomHeaderTest extends TestCase
{
    public function testGetHeaders()
    {
        $auth = new CustomHeaders([
            'X-Auth-Token' => 'Token',
            'X-Auth-Method' => 'method',
        ]);

        $headers = $auth->getHeaders();

        $this->assertArrayHasKey('X-Auth-Token', $headers);
        $this->assertArrayHasKey('X-Auth-Method', $headers);

        $this->assertEquals('Token', $headers['X-Auth-Token']);
        $this->assertEquals('method', $headers['X-Auth-Method']);

        $this->assertCount(2, $headers);
    }
}
