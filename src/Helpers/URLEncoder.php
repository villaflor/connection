<?php

namespace Villaflor\Connection\Helpers;

final class URLEncoder
{
    public static function base64UrlEncode(string $string): string
    {
        $base64Url = strtr(base64_encode($string), '+/', '-_');

        return rtrim($base64Url, '=');
    }
}
