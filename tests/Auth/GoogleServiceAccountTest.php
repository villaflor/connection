<?php

use Villaflor\Connection\Auth\GoogleServiceAccount;

it('can get Headers', function () {
    $auth = new GoogleServiceAccount(
        './tests/Fixtures/ServiceAccounts/google.json',
        'https://SERVICE.googleapis.com/'
    );

    $auth->setTokenDuration(1672980220);

    $headers = $auth->getHeaders();

    expect(isset($headers['Authorization']))->toBeTrue();

    expect($headers['Authorization'])->toBe('Bearer eyJhbGciOiJSUzI1NiIsInR5cGUiOiJKV1QiLCJraWQiOiJLRVlfSUQifQ.eyJpc3MiOiJTRVJWSUNFX0FDQ09VTlRfRU1BSUwiLCJzdWIiOiJTRVJWSUNFX0FDQ09VTlRfRU1BSUwiLCJhdWQiOiJodHRwczovL1NFUlZJQ0UuZ29vZ2xlYXBpcy5jb20vIiwiaWF0IjoxNjcyOTgwMjIwLCJleHAiOjE2NzI5ODM4MjB9.HkymqnmvcVx05VLxYBDeZXPSA84dIahIR85gwbLF_fXkwQ0UGRoR5vileQgsH-tSTvaJfbyOQoK5TE-Nsa0spg');
});

it('can set token duration', function () {
    $auth = new GoogleServiceAccount(
        './tests/Fixtures/ServiceAccounts/google.json',
        'https://SERVICE.googleapis.com/'
    );

    $auth->setTokenDuration(1672980220, 1672980220 + 3600);

    $headers = $auth->getHeaders();

    expect(isset($headers['Authorization']))->toBeTrue();

    expect($headers['Authorization'])->toBe('Bearer eyJhbGciOiJSUzI1NiIsInR5cGUiOiJKV1QiLCJraWQiOiJLRVlfSUQifQ.eyJpc3MiOiJTRVJWSUNFX0FDQ09VTlRfRU1BSUwiLCJzdWIiOiJTRVJWSUNFX0FDQ09VTlRfRU1BSUwiLCJhdWQiOiJodHRwczovL1NFUlZJQ0UuZ29vZ2xlYXBpcy5jb20vIiwiaWF0IjoxNjcyOTgwMjIwLCJleHAiOjE2NzI5ODM4MjB9.HkymqnmvcVx05VLxYBDeZXPSA84dIahIR85gwbLF_fXkwQ0UGRoR5vileQgsH-tSTvaJfbyOQoK5TE-Nsa0spg');
});
