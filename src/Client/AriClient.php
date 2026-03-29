<?php

namespace VoIPforAll\AsteriskAri\Client;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use VoIPforAll\AsteriskAri\Contracts\AriClientInterface;
use VoIPforAll\AsteriskAri\Exceptions\AriConnectionException;
use VoIPforAll\AsteriskAri\Exceptions\AriException;

class AriClient implements AriClientInterface
{
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

    private function request(string $method, string $uri, array $data = [], array $query = []): array
    {
        try {
            $response = $this->http()
                ->when($query, fn (PendingRequest $r) => $r->withQueryParameters($query))
                ->{$method}("{$this->baseUrl}/{$uri}", $data ?: null);
        } catch (ConnectionException $e) {
            throw new AriConnectionException('Failed to connect to Asterisk ARI: '.$e->getMessage(), previous: $e);
        }

        if ($response->failed()) {
            throw new AriException(
                message: $response->json('message', 'ARI request failed'),
                code: $response->status(),
            );
        }

        if ($response->noContent()) {
            return [];
        }

        return $response->json() ?? [];
    }

    private function http(): PendingRequest
    {
        return Http::withBasicAuth($this->user, $this->password)
            ->acceptJson()
            ->timeout($this->timeout);
    }
}
