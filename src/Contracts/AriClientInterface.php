<?php

namespace VoIPforAll\AsteriskAri\Contracts;

interface AriClientInterface
{
    public function get(string $uri, array $query = []): array;

    public function post(string $uri, array $data = []): array;

    public function put(string $uri, array $data = []): array;

    public function delete(string $uri, array $query = []): array;

    /**
     * Perform a GET request and return the raw response body (e.g. a stored recording's audio).
     *
     * @param  array<string, mixed>  $query
     */
    public function getRaw(string $uri, array $query = []): string;
}
