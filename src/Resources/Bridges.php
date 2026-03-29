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
        ], fn ($value) => $value !== null));
    }

    public function destroy(string $bridgeId): array
    {
        return $this->client->delete("bridges/{$bridgeId}");
    }

    public function addChannel(string $bridgeId, string|array $channels): array
    {
        return $this->client->post("bridges/{$bridgeId}/addChannel", [
            'channel' => $this->formatChannels($channels),
        ]);
    }

    public function removeChannel(string $bridgeId, string|array $channels): array
    {
        return $this->client->post("bridges/{$bridgeId}/removeChannel", [
            'channel' => $this->formatChannels($channels),
        ]);
    }

    private function formatChannels(string|array $channels): string
    {
        return is_array($channels) ? implode(',', $channels) : $channels;
    }
}
