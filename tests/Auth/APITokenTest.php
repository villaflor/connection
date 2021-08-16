<?php

namespace Auth;

use Villaflor\Connection\Auth\APIToken;

class APITokenTest extends \PHPUnit\Framework\TestCase
{
    public function testGetHeaders()
    {
        $auth = new APIToken('dXNlckBleGFtcGxlLmNvbTpzZWNyZXQ=');
        $headers = $auth->getHeaders();

        $this->assertArrayHasKey('Authorization', $headers);

        $this->assertEquals('Bearer dXNlckBleGFtcGxlLmNvbTpzZWNyZXQ=', $headers['Authorization']);

        $this->assertCount(1, $headers);
    }
}
