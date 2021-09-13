<?php

namespace Auth;

use PHPUnit\Framework\TestCase;
use Villaflor\Connection\Auth\APIKey;

class APIKeyTest extends TestCase
{
    public function testGetHeaders()
    {
        $auth = new APIKey('example@example.com', '06f7b38a-82fc-4266-a972-e64c3a046f2a');
        $headers = $auth->getHeaders();

        $this->assertArrayHasKey('X-Auth-Key', $headers);
        $this->assertArrayHasKey('X-Auth-Email', $headers);

        $this->assertEquals('example@example.com', $headers['X-Auth-Email']);
        $this->assertEquals('06f7b38a-82fc-4266-a972-e64c3a046f2a', $headers['X-Auth-Key']);

        $this->assertCount(2, $headers);
    }
}
