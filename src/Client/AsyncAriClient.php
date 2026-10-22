<?php

namespace VoIPforAll\AsteriskAri\Client;

use InvalidArgumentException;
use React\EventLoop\Loop;
use React\EventLoop\LoopInterface;
use React\Promise\Deferred;
use React\Promise\PromiseInterface;
use React\Socket\ConnectionInterface;
use React\Socket\Connector;
use React\Socket\ConnectorInterface;
use Throwable;
use VoIPforAll\AsteriskAri\Exceptions\AriConnectionException;
use VoIPforAll\AsteriskAri\Exceptions\AriException;

use function React\Promise\reject;

/**
 * Non-blocking ARI HTTP client running on the ReactPHP event loop (the same loop the WebSocket client uses),
 * so a daemon can call ARI while it keeps consuming events.
 *
 * It speaks plain HTTP/1.1 over react/socket (one connection per request, "Connection: close"), which keeps
 * the package free of react/http and its psr/http-message ^1 constraint.
 */
class AsyncAriClient
{
    private const MAX_RESPONSE_BYTES = 64 * 1024 * 1024;

    private readonly LoopInterface $loop;

    private readonly ConnectorInterface $connector;

    public function __construct(
        private readonly string $host,
        private readonly int $port,
        private readonly string $user,
        private readonly string $password,
        private readonly string $scheme = 'http',
        private readonly float $timeout = 10,
        ?LoopInterface $loop = null,
        ?ConnectorInterface $connector = null,
    ) {
        $this->loop = $loop ?? Loop::get();
        $this->connector = $connector ?? new Connector(['timeout' => $this->timeout], $this->loop);
    }

    /**
     * @param  array<string, mixed>  $query
     * @return PromiseInterface<array<mixed>>
     */
    public function get(string $uri, array $query = []): PromiseInterface
    {
        return $this->json('GET', $uri, query: $query);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return PromiseInterface<array<mixed>>
     */
    public function post(string $uri, array $data = []): PromiseInterface
    {
        return $this->json('POST', $uri, data: $data);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return PromiseInterface<array<mixed>>
     */
    public function put(string $uri, array $data = []): PromiseInterface
    {
        return $this->json('PUT', $uri, data: $data);
    }

    /**
     * @param  array<string, mixed>  $query
     * @return PromiseInterface<array<mixed>>
     */
    public function delete(string $uri, array $query = []): PromiseInterface
    {
        return $this->json('DELETE', $uri, query: $query);
    }

    /**
     * @param  array<string, mixed>  $query
     * @return PromiseInterface<string>
     */
    public function getRaw(string $uri, array $query = []): PromiseInterface
    {
        return $this->send('GET', $uri, [], $query, '*/*')
            ->then(fn (array $response) => $response['body']);
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  array<string, mixed>  $query
     * @return PromiseInterface<array<mixed>>
     */
    private function json(string $method, string $uri, array $data = [], array $query = []): PromiseInterface
    {
        return $this->send($method, $uri, $data, $query, 'application/json')
            ->then(function (array $response) {
                if ($response['status'] === 204 || trim($response['body']) === '') {
                    return [];
                }

                $decoded = json_decode($response['body'], true);

                return is_array($decoded) ? $decoded : [];
            });
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  array<string, mixed>  $query
     * @return PromiseInterface<array{status: int, headers: array<string, string>, body: string}>
     */
    private function send(string $method, string $uri, array $data, array $query, string $accept): PromiseInterface
    {
        $connection = null;
        $timer = null;
        $settled = false;

        if (preg_match('/[\x00-\x20\x7F]/', $uri)) {
            return reject(new InvalidArgumentException('Invalid ARI URI.'));
        }

        $deferred = new Deferred(function () use (&$connection, &$timer, &$settled) {
            $settled = true;
            $timer && $this->loop->cancelTimer($timer);
            $connection?->close();

            throw new AriConnectionException('ARI request cancelled');
        });

        $fail = function (Throwable $e) use ($deferred, &$connection, &$timer, &$settled): void {
            if ($settled) {
                return;
            }

            $settled = true;
            $timer && $this->loop->cancelTimer($timer);
            $connection?->close();
            $deferred->reject($e);
        };

        $timer = $this->loop->addTimer($this->timeout, fn () => $fail(new AriConnectionException('ARI request timed out')));

        $this->connector->connect($this->socketUri())->then(
            function (ConnectionInterface $conn) use ($method, $uri, $data, $query, $accept, $deferred, $fail, &$connection, &$timer, &$settled) {
                if ($settled) {
                    $conn->close();

                    return;
                }

                $connection = $conn;
                $buffer = '';

                $conn->on('data', function (string $chunk) use ($conn, &$buffer, $fail) {
                    $buffer .= $chunk;

                    if (strlen($buffer) > self::MAX_RESPONSE_BYTES) {
                        $fail(new AriException('ARI response too large'));

                        return;
                    }

                    if ($this->hasCompleteBody($buffer)) {
                        $conn->close();
                    }
                });

                $conn->on('error', fn (Throwable $e) => $fail(
                    new AriConnectionException('Failed to connect to Asterisk ARI: '.$e->getMessage(), previous: $e)
                ));

                $conn->on('close', function () use (&$buffer, $deferred, $fail, &$timer, &$settled) {
                    if ($settled) {
                        return;
                    }

                    try {
                        $response = $this->parseResponse($buffer);
                    } catch (Throwable $e) {
                        $fail($e);

                        return;
                    }

                    if ($response['status'] >= 400) {
                        $fail($this->errorFor($response));

                        return;
                    }

                    $settled = true;
                    $timer && $this->loop->cancelTimer($timer);
                    $deferred->resolve($response);
                });

                $conn->write($this->buildRequest($method, $uri, $data, $query, $accept));
            },
            fn (Throwable $e) => $fail(
                new AriConnectionException('Failed to connect to Asterisk ARI: '.$e->getMessage(), previous: $e)
            ),
        );

        return $deferred->promise();
    }

    private function socketUri(): string
    {
        $transport = $this->scheme === 'https' ? 'tls' : 'tcp';

        return "{$transport}://{$this->host}:{$this->port}";
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  array<string, mixed>  $query
     */
    private function buildRequest(string $method, string $uri, array $data, array $query, string $accept): string
    {
        $target = '/ari/'.ltrim($uri, '/');

        if ($query) {
            $target .= '?'.http_build_query($query, '', '&', PHP_QUERY_RFC3986);
        }

        $body = $data ? (string) json_encode($data, JSON_UNESCAPED_SLASHES) : '';

        $headers = [
            "{$method} {$target} HTTP/1.1",
            "Host: {$this->host}:{$this->port}",
            'Authorization: Basic '.base64_encode("{$this->user}:{$this->password}"),
            "Accept: {$accept}",
            'Connection: close',
        ];

        if ($body !== '') {
            $headers[] = 'Content-Type: application/json';
        }

        if ($body !== '' || in_array($method, ['POST', 'PUT'], true)) {
            $headers[] = 'Content-Length: '.strlen($body);
        }

        return implode("\r\n", $headers)."\r\n\r\n".$body;
    }

    /**
     * @return array{status: int, headers: array<string, string>, body: string}
     */
    private function parseResponse(string $raw): array
    {
        $separator = strpos($raw, "\r\n\r\n");

        if ($separator === false || ! preg_match('#^HTTP/1\.[01] (\d{3})#', $raw, $statusMatch)) {
            throw new AriConnectionException('Invalid HTTP response from Asterisk ARI');
        }

        $headers = [];

        foreach (array_slice(explode("\r\n", substr($raw, 0, $separator)), 1) as $line) {
            [$name, $value] = array_pad(explode(':', $line, 2), 2, '');
            $headers[strtolower(trim($name))] = trim($value);
        }

        $body = substr($raw, $separator + 4);

        if (str_contains(strtolower($headers['transfer-encoding'] ?? ''), 'chunked')) {
            $body = $this->decodeChunked($body);
        } elseif (isset($headers['content-length'])) {
            $expected = (int) $headers['content-length'];

            if (strlen($body) < $expected) {
                throw new AriConnectionException('Incomplete response from Asterisk ARI');
            }

            $body = substr($body, 0, $expected);
        }

        return ['status' => (int) $statusMatch[1], 'headers' => $headers, 'body' => $body];
    }

    /**
     * Whether a Content-Length delimited response has fully arrived (so the client need not wait for the server to close).
     */
    private function hasCompleteBody(string $buffer): bool
    {
        $separator = strpos($buffer, "\r\n\r\n");

        if ($separator === false || ! preg_match('/^content-length:\s*(\d+)\s*$/mi', substr($buffer, 0, $separator), $match)) {
            return false;
        }

        return strlen($buffer) - $separator - 4 >= (int) $match[1];
    }

    /**
     * @throws AriConnectionException when the stream ends before the terminating zero-size chunk.
     */
    private function decodeChunked(string $body): string
    {
        $decoded = '';

        while (($lineEnd = strpos($body, "\r\n")) !== false) {
            $sizeLine = trim(explode(';', substr($body, 0, $lineEnd))[0]);

            if ($sizeLine === '' || ! ctype_xdigit($sizeLine)) {
                break;
            }

            $size = (int) hexdec($sizeLine);

            if ($size === 0) {
                return $decoded;
            }

            if (strlen($body) < $lineEnd + 2 + $size) {
                break;
            }

            $decoded .= substr($body, $lineEnd + 2, $size);
            $body = substr($body, $lineEnd + 2 + $size + 2);
        }

        throw new AriConnectionException('Incomplete response from Asterisk ARI');
    }

    /**
     * @param  array{status: int, headers: array<string, string>, body: string}  $response
     */
    private function errorFor(array $response): AriException
    {
        $message = 'ARI request failed';

        if (str_contains($response['headers']['content-type'] ?? '', 'json')) {
            $decoded = json_decode($response['body'], true);

            if (is_array($decoded) && is_string($decoded['message'] ?? null)) {
                $message = $decoded['message'];
            }
        }

        return new AriException(message: $message, code: $response['status']);
    }
}
