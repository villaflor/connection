<?php

use Villaflor\Connection\Helpers\URLEncoder;

it('can encode base64 URL', function () {
    expect(URLEncoder::base64UrlEncode('1234567890-=!@#$%^&*()_+[]{}\|;:\'",./<>?abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ'))
        ->toBe('MTIzNDU2Nzg5MC09IUAjJCVeJiooKV8rW117fVx8OzonIiwuLzw-P2FiY2RlZmdoaWprbG1ub3BxcnN0dXZ3eHl6QUJDREVGR0hJSktMTU5PUFFSU1RVVldYWVo');
});
