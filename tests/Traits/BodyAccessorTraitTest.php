<?php

use Villaflor\Connection\Traits\BodyAccessorTrait;

it('can get body', function () {
    $trait = new class
    {
        use BodyAccessorTrait;

        public function __construct()
        {
            $this->body = 'This is getBody test';
        }
    };

    $this->assertEquals('This is getBody test', $trait->getBody());
});
