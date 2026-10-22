<?php

use React\EventLoop\Loop;
use React\Promise\PromiseInterface;
use React\Socket\ConnectionInterface;
use React\Socket\SocketServer;
use VoIPforAll\AsteriskAri\Client\AsyncAriClient;
use VoIPforAll\AsteriskAri\Exceptions\AriConnectionException;
use VoIPforAll\AsteriskAri\Exceptions\AriException;

/**
 * Start a fake ARI HTTP server on a random loopback port. The responder receives the raw request
 * and returns the raw HTTP response (or null to keep the connection hanging).
 */
function fakeAriServer(Closure $responder, array &$requests): SocketServer
{
    $server = new SocketServer('127.0.0.1:0');

    $server->on('connection', function (ConnectionInterface $conn) use ($responder, &$requests) {
        $buffer = '';

        $conn->on('data', function (string $chunk) use ($conn, $responder, &$buffer, &$requests) {
            $buffer .= $chunk;
            $separator = strpos($buffer, "\r\n\r\n");

            if ($separator === false) {
                return;
            }

            preg_match('/content-length:\s*(\d+)/i', substr($buffer, 0, $separator), $match);

            if (strlen($buffer) - $separator - 4 < (int) ($match[1] ?? 0)) {
                return;
            }

            $requests[] = $buffer;
            $response = $responder($buffer);

            if ($response !== null) {
                $conn->end($response);
            }
        });
    });

    return $server;
}

function asyncClientFor(SocketServer $server, float $timeout = 2): AsyncAriClient
{
    $port = (int) parse_url((string) $server->getAddress(), PHP_URL_PORT);

    return new AsyncAriClient('127.0.0.1', $port, 'asterisk', 's3cr3t', timeout: $timeout);
}

/**
 * Run the event loop until the promise settles and return [value, error].
 */
function settle(PromiseInterface $promise): array
{
    $result = [null, null];
    $settled = false;

    $promise->then(
        function ($value) use (&$result, &$settled) {
            [$result, $settled] = [[$value, null], true];
            Loop::stop();
        },
        function (Throwable $e) use (&$result, &$settled) {
            [$result, $settled] = [[null, $e], true];
            Loop::stop();
        },
    );

    if ($settled) {
        return $result;
    }

    $guard = Loop::addTimer(5, fn () => Loop::stop());
    Loop::run();
    Loop::cancelTimer($guard);

    return $result;
}

function httpResponse(int $status, string $body, string $contentType = 'application/json', array $extra = []): string
{
    $reason = [200 => 'OK', 204 => 'No Content', 404 => 'Not Found', 500 => 'Internal Server Error'][$status] ?? 'OK';
    $headers = array_merge(["HTTP/1.1 {$status} {$reason}", "Content-Type: {$contentType}", 'Content-Length: '.strlen($body)], $extra);

    return implode("\r\n", $headers)."\r\n\r\n".$body;
}

beforeEach(function () {
    $this->requests = [];
});

afterEach(function () {
    $this->server?->close();
});

it('resolves a GET with the decoded JSON and sends basic auth', function () {
    $this->server = fakeAriServer(fn () => httpResponse(200, '[{"id":"ch-1"}]'), $this->requests);

    [$value, $error] = settle(asyncClientFor($this->server)->get('channels', ['only' => 'a b']));

    expect($error)->toBeNull()
        ->and($value)->toBe([['id' => 'ch-1']])
        ->and($this->requests[0])->toStartWith("GET /ari/channels?only=a%20b HTTP/1.1\r\n")
        ->toContain('Authorization: Basic '.base64_encode('asterisk:s3cr3t'))
        ->toContain('Connection: close');
});

it('sends a JSON body on POST', function () {
    $this->server = fakeAriServer(fn () => httpResponse(200, '{"id":"pb-1"}'), $this->requests);

    [$value] = settle(asyncClientFor($this->server)->post('channels/ch-1/play', ['media' => 'sound:beep']));

    expect($value)->toBe(['id' => 'pb-1'])
        ->and($this->requests[0])->toStartWith('POST /ari/channels/ch-1/play HTTP/1.1')
        ->toContain('Content-Type: application/json')
        ->toEndWith('{"media":"sound:beep"}');
});

it('sends an empty POST with a zero content length', function () {
    $this->server = fakeAriServer(fn () => httpResponse(204, '', 'text/plain'), $this->requests);

    [$value, $error] = settle(asyncClientFor($this->server)->post('channels/ch-1/answer'));

    expect($error)->toBeNull()
        ->and($value)->toBe([])
        ->and($this->requests[0])->toContain('Content-Length: 0');
});

it('supports PUT and DELETE', function () {
    $this->server = fakeAriServer(fn () => httpResponse(204, ''), $this->requests);
    $client = asyncClientFor($this->server);

    settle($client->put('asterisk/modules/res_pjsip.so'));
    settle($client->delete('channels/ch-1', ['reason_code' => 'normal']));

    expect($this->requests[0])->toStartWith('PUT /ari/asterisk/modules/res_pjsip.so HTTP/1.1')
        ->and($this->requests[1])->toStartWith('DELETE /ari/channels/ch-1?reason_code=normal HTTP/1.1');
});

it('resolves getRaw with the binary body', function () {
    $audio = "RIFF\x00\x00\x01\xffWAVE";
    $this->server = fakeAriServer(fn () => httpResponse(200, $audio, 'audio/wav'), $this->requests);

    [$value] = settle(asyncClientFor($this->server)->getRaw('recordings/stored/rec/file'));

    expect($value)->toBe($audio)
        ->and($this->requests[0])->toContain('Accept: */*');
});

it('decodes chunked responses', function () {
    $this->server = fakeAriServer(
        fn () => "HTTP/1.1 200 OK\r\nContent-Type: application/json\r\nTransfer-Encoding: chunked\r\n\r\n5\r\n{\"a\":\r\n2\r\n1}\r\n0\r\n\r\n",
        $this->requests,
    );

    [$value] = settle(asyncClientFor($this->server)->get('x'));

    expect($value)->toBe(['a' => 1]);
});

it('rejects with AriException carrying the ARI message and status', function () {
    $this->server = fakeAriServer(fn () => httpResponse(404, '{"message":"Channel not found"}'), $this->requests);

    [, $error] = settle(asyncClientFor($this->server)->get('channels/missing'));

    expect($error)->toBeInstanceOf(AriException::class)
        ->and($error->getMessage())->toBe('Channel not found')
        ->and($error->getCode())->toBe(404);
});

it('uses a generic message for non JSON errors', function () {
    $this->server = fakeAriServer(fn () => httpResponse(500, '<html>s3cr3t</html>', 'text/html'), $this->requests);

    [, $error] = settle(asyncClientFor($this->server)->get('x'));

    expect($error)->toBeInstanceOf(AriException::class)
        ->and($error->getMessage())->toBe('ARI request failed')
        ->and($error->getCode())->toBe(500);
});

it('rejects with AriConnectionException when nothing listens', function () {
    $this->server = fakeAriServer(fn () => null, $this->requests);
    $client = asyncClientFor($this->server);
    $this->server->close();
    $this->server = null;

    [, $error] = settle($client->get('channels'));

    expect($error)->toBeInstanceOf(AriConnectionException::class)
        ->and($error->getMessage())->not->toContain('s3cr3t');
});

it('times out when the server never answers', function () {
    $this->server = fakeAriServer(fn () => null, $this->requests);

    [, $error] = settle(asyncClientFor($this->server, timeout: 0.2)->get('channels'));

    expect($error)->toBeInstanceOf(AriConnectionException::class)
        ->and($error->getMessage())->toBe('ARI request timed out');
});

it('rejects a malformed HTTP response', function () {
    $this->server = fakeAriServer(fn () => "garbage\r\n\r\n", $this->requests);

    [, $error] = settle(asyncClientFor($this->server)->get('x'));

    expect($error)->toBeInstanceOf(AriConnectionException::class);
});

it('can be cancelled', function () {
    $this->server = fakeAriServer(fn () => null, $this->requests);

    $promise = asyncClientFor($this->server)->get('channels');
    $promise->cancel();

    [, $error] = settle($promise);

    expect($error)->toBeInstanceOf(AriConnectionException::class)
        ->and($error->getMessage())->toBe('ARI request cancelled');
});

it('rejects URIs with control characters or spaces without connecting', function () {
    $this->server = fakeAriServer(fn () => httpResponse(200, '{}'), $this->requests);

    [, $error] = settle(asyncClientFor($this->server)->get("channels\r\nX-Injected: 1"));

    expect($error)->toBeInstanceOf(InvalidArgumentException::class)
        ->and($this->requests)->toBe([]);
});

it('rejects a Content-Length body cut short by a dropped connection', function () {
    $this->server = fakeAriServer(
        fn () => "HTTP/1.1 200 OK\r\nContent-Type: audio/wav\r\nContent-Length: 100\r\n\r\nRIFF-partial",
        $this->requests,
    );

    [$value, $error] = settle(asyncClientFor($this->server)->getRaw('recordings/stored/rec/file'));

    expect($value)->toBeNull()
        ->and($error)->toBeInstanceOf(AriConnectionException::class)
        ->and($error->getMessage())->toBe('Incomplete response from Asterisk ARI');
});

it('rejects a chunked body without the terminating zero-size chunk', function () {
    $this->server = fakeAriServer(
        fn () => "HTTP/1.1 200 OK\r\nContent-Type: audio/wav\r\nTransfer-Encoding: chunked\r\n\r\n4\r\nRIFF\r\n",
        $this->requests,
    );

    [$value, $error] = settle(asyncClientFor($this->server)->getRaw('recordings/stored/rec/file'));

    expect($value)->toBeNull()
        ->and($error)->toBeInstanceOf(AriConnectionException::class);
});

it('rejects a chunked body whose last chunk is truncated', function () {
    $this->server = fakeAriServer(
        fn () => "HTTP/1.1 200 OK\r\nTransfer-Encoding: chunked\r\n\r\n10\r\nRIFF",
        $this->requests,
    );

    [, $error] = settle(asyncClientFor($this->server)->getRaw('recordings/stored/rec/file'));

    expect($error)->toBeInstanceOf(AriConnectionException::class);
});

it('decodes a complete chunked body', function () {
    $this->server = fakeAriServer(
        fn () => "HTTP/1.1 200 OK\r\nTransfer-Encoding: chunked\r\n\r\n4\r\nRIFF\r\n3\r\ndat\r\n0\r\n\r\n",
        $this->requests,
    );

    [$value, $error] = settle(asyncClientFor($this->server)->getRaw('recordings/stored/rec/file'));

    expect($error)->toBeNull()->and($value)->toBe('RIFFdat');
});
