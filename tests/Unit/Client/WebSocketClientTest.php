<?php

use VoIPforAll\AsteriskAri\Client\WebSocketClient;

it('builds the websocket URL with proper encoding', function () {
    $ws = new WebSocketClient(
        host: '192.168.1.10',
        port: 8088,
        user: 'admin',
        password: 'p@ss&word=1',
        app: 'my app',
        scheme: 'ws',
    );

    $reflection = new ReflectionClass($ws);
    $urlProperty = $reflection->getProperty('url');
    $url = $urlProperty->getValue($ws);

    expect($url)
        ->toStartWith('ws://192.168.1.10:8088/ari/events?')
        ->toContain('api_key=admin%3Ap%40ss%26word%3D1')
        ->toContain('app=my+app')
        ->toContain('subscribeAll=true');
});

it('builds wss URL when scheme is wss', function () {
    $ws = new WebSocketClient(
        host: 'pbx.example.com',
        port: 8089,
        user: 'admin',
        password: 'secret',
        app: 'laravel',
        scheme: 'wss',
    );

    $reflection = new ReflectionClass($ws);
    $urlProperty = $reflection->getProperty('url');
    $url = $urlProperty->getValue($ws);

    expect($url)->toStartWith('wss://pbx.example.com:8089/ari/events?');
});

it('defaults to ws scheme', function () {
    $ws = new WebSocketClient(
        host: '127.0.0.1',
        port: 8088,
        user: 'test',
        password: 'test',
        app: 'app',
    );

    $reflection = new ReflectionClass($ws);
    $urlProperty = $reflection->getProperty('url');
    $url = $urlProperty->getValue($ws);

    expect($url)->toStartWith('ws://');
});

it('resets reconnect state on stop', function () {
    $ws = new WebSocketClient(
        host: '127.0.0.1',
        port: 8088,
        user: 'test',
        password: 'test',
        app: 'app',
    );

    $ws->stop();

    $reflection = new ReflectionClass($ws);
    $shouldReconnect = $reflection->getProperty('shouldReconnect');

    expect($shouldReconnect->getValue($ws))->toBeFalse();
});
