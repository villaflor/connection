<?php

namespace Villaflor\Connection\Helpers;

final class Encryption
{
    public static function rs256(string $data, string $privateKey): string
    {
        openssl_sign($data, $signature, $privateKey, 'sha256WithRSAEncryption');

        return $signature;
    }

    public static function sha256(string $data, string $key): string
    {
        return hash_hmac('SHA256', $data, $key, true);
    }
}
