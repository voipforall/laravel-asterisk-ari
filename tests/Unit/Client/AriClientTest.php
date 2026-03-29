<?php

use Illuminate\Support\Facades\Http;
use VoIPforAll\AsteriskAri\Client\AriClient;
use VoIPforAll\AsteriskAri\Exceptions\AriConnectionException;
use VoIPforAll\AsteriskAri\Exceptions\AriException;
use VoIPforAll\AsteriskAri\Tests\TestCase;

uses(TestCase::class);

beforeEach(function () {
    $this->client = new AriClient(
        host: '127.0.0.1',
        port: 8088,
        user: 'asterisk',
        password: 'secret',
        scheme: 'http',
        timeout: 5,
    );
});

it('makes a GET request', function () {
    Http::fake([
        'http://127.0.0.1:8088/ari/channels' => Http::response([
            ['id' => 'ch-1'],
            ['id' => 'ch-2'],
        ]),
    ]);

    $result = $this->client->get('channels');

    expect($result)->toHaveCount(2);

    Http::assertSent(fn ($request) => $request->url() === 'http://127.0.0.1:8088/ari/channels'
        && $request->hasHeader('Authorization')
    );
});

it('makes a GET request with query parameters', function () {
    Http::fake([
        'http://127.0.0.1:8088/ari/asterisk/info*' => Http::response([
            'system' => ['entity_id' => 'abc'],
        ]),
    ]);

    $result = $this->client->get('asterisk/info', ['only' => 'system']);

    expect($result)->toHaveKey('system');

    Http::assertSent(fn ($request) => str_contains($request->method(), 'GET')
        && str_contains($request->url(), 'asterisk/info')
    );
});

it('makes a POST request with data', function () {
    Http::fake([
        'http://127.0.0.1:8088/ari/channels' => Http::response(['id' => 'ch-new']),
    ]);

    $result = $this->client->post('channels', ['endpoint' => 'PJSIP/1000']);

    expect($result)->toHaveKey('id', 'ch-new');

    Http::assertSent(fn ($request) => $request->method() === 'POST');
});

it('makes a PUT request', function () {
    Http::fake([
        'http://127.0.0.1:8088/ari/deviceStates/Custom:dev1' => Http::response([], 200),
    ]);

    $result = $this->client->put('deviceStates/Custom:dev1', ['deviceState' => 'BUSY']);

    expect($result)->toBeArray();

    Http::assertSent(fn ($request) => $request->method() === 'PUT');
});

it('makes a DELETE request', function () {
    Http::fake([
        'http://127.0.0.1:8088/ari/channels/ch-123*' => Http::response([], 204),
    ]);

    $result = $this->client->delete('channels/ch-123', ['reason_code' => 'normal']);

    expect($result)->toBe([]);
});

it('returns empty array for 204 No Content', function () {
    Http::fake([
        'http://127.0.0.1:8088/ari/channels/ch-123/answer' => Http::response([], 204),
    ]);

    $result = $this->client->post('channels/ch-123/answer');

    expect($result)->toBe([]);
});

it('throws AriException on 4xx/5xx response', function () {
    Http::fake([
        'http://127.0.0.1:8088/ari/channels/invalid' => Http::response([
            'message' => 'Channel not found',
        ], 404),
    ]);

    $this->client->get('channels/invalid');
})->throws(AriException::class, 'Channel not found');

it('throws AriConnectionException on connection failure', function () {
    Http::fake(fn () => throw new \Illuminate\Http\Client\ConnectionException('Connection refused'));

    $this->client->get('channels');
})->throws(AriConnectionException::class, 'Failed to connect to Asterisk ARI');

it('does not expose host in connection error message', function () {
    Http::fake(fn () => throw new \Illuminate\Http\Client\ConnectionException('timeout'));

    try {
        $this->client->get('channels');
    } catch (AriConnectionException $e) {
        expect($e->getMessage())->not->toContain('127.0.0.1:8088');
    }
});

it('sends basic auth credentials', function () {
    Http::fake([
        'http://127.0.0.1:8088/ari/asterisk/info' => Http::response(['build' => []]),
    ]);

    $this->client->get('asterisk/info');

    Http::assertSent(function ($request) {
        $auth = $request->header('Authorization')[0] ?? '';

        return str_starts_with($auth, 'Basic ')
            && base64_decode(substr($auth, 6)) === 'asterisk:secret';
    });
});
