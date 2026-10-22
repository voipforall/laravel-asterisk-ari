<?php

use Illuminate\Http\Client\ConnectionException;
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

    Http::assertSent(fn ($request) => $request->method() === 'GET'
        && $request->url() === 'http://127.0.0.1:8088/ari/asterisk/info?only=system'
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

    $result = $this->client->delete('channels/ch-123', ['reason' => 'normal']);

    expect($result)->toBe([]);

    Http::assertSent(fn ($request) => $request->method() === 'DELETE'
        && $request->url() === 'http://127.0.0.1:8088/ari/channels/ch-123?reason=normal'
    );
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
    Http::fake(fn () => throw new ConnectionException('Connection refused'));

    $this->client->get('channels');
})->throws(AriConnectionException::class, 'Failed to connect to Asterisk ARI');

it('does not expose host in connection error message', function () {
    Http::fake(fn () => throw new ConnectionException('timeout'));

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

it('returns the raw body on getRaw', function () {
    Http::fake([
        'http://127.0.0.1:8088/ari/recordings/stored/rec/file' => Http::response("RIFF\x00binary", 200, ['Content-Type' => 'audio/wav']),
    ]);

    expect($this->client->getRaw('recordings/stored/rec/file'))->toBe("RIFF\x00binary");

    Http::assertSent(function ($request) {
        $auth = $request->header('Authorization')[0] ?? '';

        return $request->method() === 'GET'
            && base64_decode(substr($auth, 6)) === 'asterisk:secret';
    });
});

it('sends query parameters on getRaw', function () {
    Http::fake(['*' => Http::response('x')]);

    $this->client->getRaw('some/file', ['a' => 'b']);

    Http::assertSent(fn ($request) => str_ends_with($request->url(), 'some/file?a=b'));
});

it('throws AriException on a failed getRaw', function () {
    Http::fake([
        '*' => Http::response(['message' => 'Recording not found'], 404),
    ]);

    $this->client->getRaw('recordings/stored/missing/file');
})->throws(AriException::class, 'Recording not found');

it('uses a generic message when a failed getRaw body is not JSON', function () {
    Http::fake([
        '*' => Http::response('<html>boom</html>', 500, ['Content-Type' => 'text/html']),
    ]);

    try {
        $this->client->getRaw('recordings/stored/x/file');
        $this->fail('Expected AriException');
    } catch (AriException $e) {
        expect($e->getMessage())->toBe('ARI request failed')
            ->and($e->getCode())->toBe(500);
    }
});

it('throws AriConnectionException on a getRaw connection failure', function () {
    Http::fake(fn () => throw new ConnectionException('Connection refused'));

    $this->client->getRaw('recordings/stored/x/file');
})->throws(AriConnectionException::class);

it('rejects a getRaw response whose declared length exceeds the limit', function () {
    Http::fake([
        '*' => Http::response('x', 200, ['Content-Length' => (string) (64 * 1024 * 1024 + 1)]),
    ]);

    $this->client->getRaw('recordings/stored/huge/file');
})->throws(AriException::class, 'ARI response too large');
