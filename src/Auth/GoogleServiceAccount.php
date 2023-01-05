<?php

namespace Villaflor\Connection\Auth;

use Villaflor\Connection\Helpers\Encryption;
use Villaflor\Connection\Helpers\URLEncoder;

class GoogleServiceAccount implements AuthInterface
{
    private string $privateKeyId;

    private string $privateKey;

    private string $clientEmail;

    private string $apiEndpoint;

    public function __construct(string $filepath, string $apiEndpoint)
    {
        $serviceAccount = json_decode(file_get_contents($filepath));

        $this->privateKeyId = $serviceAccount->private_key_id;

        $this->privateKey = $serviceAccount->private_key;

        $this->clientEmail = $serviceAccount->client_email;

        $this->apiEndpoint = $apiEndpoint;
    }

    public function getHeaders(): array
    {
        return [
            'Authorization' => 'Bearer '.$this->getJWT(),
        ];
    }

    private function getJWT(): string
    {
        $header = json_encode($this->getJWTHeader(), JSON_UNESCAPED_SLASHES);

        $claim = json_encode($this->getJWTClaim(), JSON_UNESCAPED_SLASHES);

        $jwtSigning = URLEncoder::base64UrlEncode($header).'.'.URLEncoder::base64UrlEncode($claim);

        $signature = Encryption::rs256($jwtSigning, $this->privateKey);

        $jwtSignature = URLEncoder::base64UrlEncode($signature);

        return $jwtSigning.'.'.$jwtSignature;
    }

    private function getJWTHeader(): array
    {
        return [
            'alg' => 'RS256',
            'type' => 'JWT',
            'kid' => $this->privateKeyId,
        ];
    }

    private function getJWTClaim(): array
    {
        return [
            'iss' => $this->clientEmail,
            'sub' => $this->clientEmail,
            'aud' => $this->apiEndpoint,
            'iat' => time(),
            'exp' => time() + 3600,
        ];
    }
}
