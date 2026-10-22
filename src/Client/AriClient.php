<?php

namespace VoIPforAll\AsteriskAri\Client;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use VoIPforAll\AsteriskAri\Contracts\RawAriClientInterface;
use VoIPforAll\AsteriskAri\Exceptions\AriConnectionException;
use VoIPforAll\AsteriskAri\Exceptions\AriException;

class AriClient implements RawAriClientInterface
{
    private const MAX_RAW_RESPONSE_BYTES = 64 * 1024 * 1024;

    private string $baseUrl;

    public function __construct(
        private readonly string $host,
        private readonly int $port,
        private readonly string $user,
        private readonly string $password,
        private readonly string $scheme = 'http',
        private readonly int $timeout = 10,
    ) {
        $this->baseUrl = "{$this->scheme}://{$this->host}:{$this->port}/ari";
    }

    public function get(string $uri, array $query = []): array
    {
        return $this->request('get', $uri, query: $query);
    }

    public function post(string $uri, array $data = []): array
    {
        return $this->request('post', $uri, data: $data);
    }

    public function put(string $uri, array $data = []): array
    {
        return $this->request('put', $uri, data: $data);
    }

    public function delete(string $uri, array $query = []): array
    {
        return $this->request('delete', $uri, query: $query);
    }

    /**
     * Download a binary body, streaming it so a runaway response cannot exhaust memory.
     *
     * @throws AriException when the response exceeds the size limit.
     */
    public function getRaw(string $uri, array $query = []): string
    {
        $response = $this->send('get', $uri, query: $query, accept: '*/*', options: ['stream' => true]);

        $declaredLength = $response->header('Content-Length');

        if ($declaredLength !== '' && (int) $declaredLength > self::MAX_RAW_RESPONSE_BYTES) {
            throw new AriException('ARI response too large');
        }

        $stream = $response->toPsrResponse()->getBody();
        $body = '';

        while (! $stream->eof()) {
            $body .= $stream->read(65536);

            if (strlen($body) > self::MAX_RAW_RESPONSE_BYTES) {
                $stream->close();

                throw new AriException('ARI response too large');
            }
        }

        return $body;
    }

    private function request(string $method, string $uri, array $data = [], array $query = []): array
    {
        $response = $this->send($method, $uri, $data, $query);

        if ($response->noContent()) {
            return [];
        }

        return $response->json() ?? [];
    }

    /**
     * @param  array<string, mixed>  $options  extra Guzzle request options
     */
    private function send(string $method, string $uri, array $data = [], array $query = [], string $accept = 'application/json', array $options = []): Response
    {
        try {
            $request = $this->http($accept)
                ->when($options, fn (PendingRequest $r) => $r->withOptions($options))
                ->when($query, fn (PendingRequest $r) => $r->withQueryParameters($query));

            // PendingRequest::get($url, null) would replace the query parameters set above with null.
            $response = $method === 'get'
                ? $request->get("{$this->baseUrl}/{$uri}")
                : $request->{$method}("{$this->baseUrl}/{$uri}", $data ?: null);
        } catch (ConnectionException $e) {
            throw new AriConnectionException('Failed to connect to Asterisk ARI: '.$e->getMessage(), previous: $e);
        }

        $this->throwIfFailed($response);

        return $response;
    }

    private function throwIfFailed(Response $response): void
    {
        if (! $response->failed()) {
            return;
        }

        $message = str_contains((string) $response->header('Content-Type'), 'json')
            ? $response->json('message', 'ARI request failed')
            : 'ARI request failed';

        throw new AriException(message: (string) $message, code: $response->status());
    }

    private function http(string $accept): PendingRequest
    {
        return Http::withBasicAuth($this->user, $this->password)
            ->accept($accept)
            ->timeout($this->timeout);
    }
}
