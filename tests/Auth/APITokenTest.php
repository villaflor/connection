<?php

use Villaflor\Connection\Auth\APIToken;

it('can get Headers', function () {
    $auth = new APIToken('dXNlckBleGFtcGxlLmNvbTpzZWNyZXQ=');
    $headers = $auth->getHeaders();

    $this->assertArrayHasKey('Authorization', $headers);

    $this->assertEquals('Bearer dXNlckBleGFtcGxlLmNvbTpzZWNyZXQ=', $headers['Authorization']);

    $this->assertCount(1, $headers);
});
