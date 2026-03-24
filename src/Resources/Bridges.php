<?php

namespace VoIPforAll\AsteriskAri\Resources;

use VoIPforAll\AsteriskAri\Contracts\AriClientInterface;

class Bridges
{
    public function __construct(
        private readonly AriClientInterface $client,
    ) {}

    public function list(): array
    {
        return $this->client->get('bridges');
    }

    public function get(string $bridgeId): array
    {
        return $this->client->get("bridges/{$bridgeId}");
    }

    public function create(string $type = 'mixing', ?string $name = null): array
    {
        return $this->client->post('bridges', array_filter([
            'type' => $type,
            'name' => $name,
        ]));
    }

    public function destroy(string $bridgeId): array
    {
        return $this->client->delete("bridges/{$bridgeId}");
    }

    public function addChannel(string $bridgeId, string|array $channels): array
    {
        return $this->client->post("bridges/{$bridgeId}/addChannel", [
            'channel' => is_array($channels) ? implode(',', $channels) : $channels,
        ]);
    }

    public function removeChannel(string $bridgeId, string|array $channels): array
    {
        return $this->client->post("bridges/{$bridgeId}/removeChannel", [
            'channel' => is_array($channels) ? implode(',', $channels) : $channels,
        ]);
    }
}
