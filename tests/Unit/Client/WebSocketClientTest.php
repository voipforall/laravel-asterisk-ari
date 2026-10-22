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
    $buildUrl = $reflection->getMethod('buildUrl');
    $url = $buildUrl->invoke($ws);

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
    $buildUrl = $reflection->getMethod('buildUrl');
    $url = $buildUrl->invoke($ws);

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
    $buildUrl = $reflection->getMethod('buildUrl');
    $url = $buildUrl->invoke($ws);

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

function websocketUrl(WebSocketClient $ws): string
{
    return (new ReflectionClass($ws))->getMethod('buildUrl')->invoke($ws);
}

it('subscribes to all events by default', function () {
    $ws = new WebSocketClient('127.0.0.1', 8088, 'u', 'p', 'app');

    expect(websocketUrl($ws))->toContain('subscribeAll=true')
        ->and($ws->subscribesAll())->toBeTrue();
});

it('can disable subscribeAll', function () {
    $ws = new WebSocketClient('127.0.0.1', 8088, 'u', 'p', 'voicebot', subscribeAll: false);

    expect(websocketUrl($ws))->toContain('subscribeAll=false')
        ->not->toContain('subscribeAll=true');
});

it('joins several applications with commas', function () {
    $ws = new WebSocketClient('127.0.0.1', 8088, 'u', 'p', ['voicebot', 'sentiment']);

    expect(websocketUrl($ws))->toContain('app=voicebot%2Csentiment')
        ->and($ws->app())->toBe('voicebot,sentiment');
});

it('accepts a comma-separated application string', function () {
    $ws = new WebSocketClient('127.0.0.1', 8088, 'u', 'p', 'voicebot,sentiment');

    expect(websocketUrl($ws))->toContain('app=voicebot%2Csentiment');
});

it('builds from config with overrides', function () {
    $config = [
        'host' => 'pbx', 'port' => '8089', 'user' => 'u', 'password' => 'p',
        'app' => 'laravel', 'ws_scheme' => 'wss', 'subscribe_all' => true,
    ];

    $default = WebSocketClient::fromConfig($config);
    $custom = WebSocketClient::fromConfig($config, ['voicebot'], false);

    expect(websocketUrl($default))->toStartWith('wss://pbx:8089/ari/events?')
        ->toContain('app=laravel')
        ->toContain('subscribeAll=true')
        ->and(websocketUrl($custom))->toContain('app=voicebot')
        ->toContain('subscribeAll=false');
});

it('reads subscribe_all from config strings', function () {
    $ws = WebSocketClient::fromConfig([
        'host' => 'pbx', 'port' => 8088, 'user' => 'u', 'password' => 'p', 'app' => 'a', 'subscribe_all' => 'false',
    ]);

    expect($ws->subscribesAll())->toBeFalse();
});

it('redacts credentials from connection errors', function () {
    $ws = new WebSocketClient('127.0.0.1', 8088, 'admin', 'p@ss&word', 'app');
    $redact = (new ReflectionClass($ws))->getMethod('redact');

    $error = $redact->invoke($ws, new RuntimeException(
        'Connection to ws://127.0.0.1:8088/ari/events?api_key=admin%3Ap%40ss%26word&app=app cancelled during handshake'
    ));

    expect($error->getMessage())->not->toContain('p%40ss')
        ->not->toContain('p@ss')
        ->toContain('api_key=***');
});

it('keeps errors without credentials untouched', function () {
    $ws = new WebSocketClient('127.0.0.1', 8088, 'admin', 'secret', 'app');
    $original = new RuntimeException('Connection refused');

    expect((new ReflectionClass($ws))->getMethod('redact')->invoke($ws, $original))->toBe($original);
});
