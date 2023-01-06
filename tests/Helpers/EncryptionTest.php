<?php

use Villaflor\Connection\Helpers\Encryption;
use Villaflor\Connection\Helpers\URLEncoder;

it('can encrypt with RS256 algorithm', function () {
    expect(URLEncoder::base64UrlEncode(Encryption::rs256(
        '1234567890-=!@#$%^&*()_+[]{}\|;:\'",./<>?abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ',
        file_get_contents('./tests/Fixtures/RSA/private')
    )))->toBe('PlzluEOkqqooCnRBNq3SPz3_p_WoYsQLD9NUeRq_G_gN7f_acm27x3ANm3IXYS1CzvMopRRe9fpeo6VwibD6sA');
});

it('can encrypt with SHA256 algorithm', function () {
    expect(URLEncoder::base64UrlEncode(Encryption::sha256(
        '1234567890-=!@#$%^&*()_+[]{}\|;:\'",./<>?abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ',
        'private_key'
    )))->toBe('4JBbTMxS2ni7WIy_awqXwFentGbEBSa_NvT4baVVTzI');
});
