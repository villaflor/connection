<?php

use Villaflor\Connection\Adapter\Curl;
use Villaflor\Connection\Auth\APIToken;
use Villaflor\Connection\ConnectionBuilder;

it('can create a builder instance', function () {
    $builder = ConnectionBuilder::create();

    expect($builder)->toBeInstanceOf(ConnectionBuilder::class);
});

it('can build client with bearer token', function () {
    $client = ConnectionBuilder::create()
        ->withBaseUri('https://api.example.com')
        ->withBearerToken('test-token')
        ->build();

    expect($client)->toBeInstanceOf(Curl::class);
});

it('can build client with api key', function () {
    $client = ConnectionBuilder::create()
        ->withBaseUri('https://api.example.com')
        ->withApiKey('test@example.com', 'api-key')
        ->build();

    expect($client)->toBeInstanceOf(Curl::class);
});

it('can build client with custom headers', function () {
    $client = ConnectionBuilder::create()
        ->withBaseUri('https://api.example.com')
        ->withCustomHeaders(['X-Custom' => 'value'])
        ->build();

    expect($client)->toBeInstanceOf(Curl::class);
});

it('can build client with user service key', function () {
    $client = ConnectionBuilder::create()
        ->withBaseUri('https://api.example.com')
        ->withUserServiceKey('user-service-key')
        ->build();

    expect($client)->toBeInstanceOf(Curl::class);
});

it('can build client without auth', function () {
    $client = ConnectionBuilder::create()
        ->withBaseUri('https://api.example.com')
        ->withoutAuth()
        ->build();

    expect($client)->toBeInstanceOf(Curl::class);
});

it('can build client with custom auth', function () {
    $auth = new APIToken('custom-token');

    $client = ConnectionBuilder::create()
        ->withBaseUri('https://api.example.com')
        ->withAuth($auth)
        ->build();

    expect($client)->toBeInstanceOf(Curl::class);
});

it('can set timeout', function () {
    $client = ConnectionBuilder::create()
        ->withBaseUri('https://api.example.com')
        ->withBearerToken('token')
        ->withTimeout(60)
        ->build();

    expect($client)->toBeInstanceOf(Curl::class);
});

it('can set connect timeout', function () {
    $client = ConnectionBuilder::create()
        ->withBaseUri('https://api.example.com')
        ->withBearerToken('token')
        ->withConnectTimeout(5)
        ->build();

    expect($client)->toBeInstanceOf(Curl::class);
});

it('can chain all builder methods', function () {
    $client = ConnectionBuilder::create()
        ->withBaseUri('https://api.example.com')
        ->withBearerToken('token')
        ->withTimeout(60)
        ->withConnectTimeout(5)
        ->build();

    expect($client)->toBeInstanceOf(Curl::class);
});

it('throws exception when base uri is missing', function () {
    expect(fn () => ConnectionBuilder::create()->build())
        ->toThrow(InvalidArgumentException::class, 'Base URI is required');
});

it('uses no auth by default when auth not specified', function () {
    $client = ConnectionBuilder::create()
        ->withBaseUri('https://api.example.com')
        ->build();

    expect($client)->toBeInstanceOf(Curl::class);
});

it('can set google service account auth method', function () {
    // Create a valid temporary RSA key pair
    $config = [
        'private_key_bits' => 2048,
        'private_key_type' => OPENSSL_KEYTYPE_RSA,
    ];

    $res = openssl_pkey_new($config);
    openssl_pkey_export($res, $privateKey);
    $details = openssl_pkey_get_details($res);

    $tempFile = tempnam(sys_get_temp_dir(), 'gsa_');
    file_put_contents($tempFile, json_encode([
        'private_key_id' => 'test-key-id',
        'private_key' => $privateKey,
        'client_email' => 'test@test-project.iam.gserviceaccount.com',
    ]));

    try {
        $client = ConnectionBuilder::create()
            ->withBaseUri('https://api.example.com')
            ->withGoogleServiceAccount($tempFile, 'https://www.googleapis.com/auth/cloud-platform')
            ->build();

        expect($client)->toBeInstanceOf(Curl::class);
    } finally {
        unlink($tempFile);
    }
});
