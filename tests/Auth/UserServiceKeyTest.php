<?php

namespace Auth;

use PHPUnit\Framework\TestCase;
use Villaflor\Connection\Auth\UserServiceKey;

class UserServiceKeyTest extends TestCase
{
    public function testGetHeaders()
    {
        $auth = new UserServiceKey('v1.1-asduyrweruhfwjdbqwryfqw8f7qw8e98rqw9eriqimexfuw89emruw89er9nx47yr239458293845023m4x5340x230my49234xt9nm234tx8982349t8x390t2903u4t923mu4t283yt983nvy5t90n283-5md2u35yft230dtu235f8yt2395dy3n5t90234um0tm23u40t');
        $headers = $auth->getHeaders();

        $this->assertArrayHasKey('X-Auth-User-Service-Key', $headers);

        $this->assertEquals(
            'v1.1-asduyrweruhfwjdbqwryfqw8f7qw8e98rqw9eriqimexfuw89emruw89er9nx47yr239458293845023m4x5340x230my49234xt9nm234tx8982349t8x390t2903u4t923mu4t283yt983nvy5t90n283-5md2u35yft230dtu235f8yt2395dy3n5t90234um0tm23u40t',
            $headers['X-Auth-User-Service-Key']
        );

        $this->assertCount(1, $headers);
    }
}
