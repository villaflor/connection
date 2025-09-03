<?php

use Villaflor\Connection\Auth\None;

it('can get Headers', function () {
    $auth = new None;
    $headers = $auth->getHeaders();

    $this->assertEquals([], $headers);
});
