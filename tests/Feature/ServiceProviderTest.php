<?php

use VoIPforAll\AsteriskAri\AriManager;
use VoIPforAll\AsteriskAri\Client\AriClient;
use VoIPforAll\AsteriskAri\Client\WebSocketClient;
use VoIPforAll\AsteriskAri\Contracts\AriClientInterface;

it('registers AriClientInterface as singleton', function () {
    $client1 = app(AriClientInterface::class);
    $client2 = app(AriClientInterface::class);

    expect($client1)
        ->toBeInstanceOf(AriClient::class)
        ->toBe($client2);
});

it('registers AriManager as singleton', function () {
    $manager1 = app(AriManager::class);
    $manager2 = app(AriManager::class);

    expect($manager1)
        ->toBeInstanceOf(AriManager::class)
        ->toBe($manager2);
});

it('registers WebSocketClient as singleton', function () {
    $ws1 = app(WebSocketClient::class);
    $ws2 = app(WebSocketClient::class);

    expect($ws1)
        ->toBeInstanceOf(WebSocketClient::class)
        ->toBe($ws2);
});

it('loads configuration with correct defaults', function () {
    expect(config('asterisk.host'))->toBe('127.0.0.1')
        ->and(config('asterisk.port'))->toBe(8088)
        ->and(config('asterisk.user'))->toBe('test')
        ->and(config('asterisk.password'))->toBe('test123')
        ->and(config('asterisk.app'))->toBe('test-app')
        ->and(config('asterisk.scheme'))->toBe('http')
        ->and(config('asterisk.ws_scheme'))->toBe('ws')
        ->and(config('asterisk.timeout'))->toBe(10);
});

it('resolves AriManager through the Ari facade', function () {
    $manager = VoIPforAll\AsteriskAri\Facades\Ari::getFacadeRoot();

    expect($manager)->toBeInstanceOf(AriManager::class);
});
