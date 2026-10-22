<?php

namespace VoIPforAll\AsteriskAri\Resources;

use VoIPforAll\AsteriskAri\Concerns\BuildsAriPaths;
use VoIPforAll\AsteriskAri\Contracts\AriClientInterface;

class Bridges
{
    use BuildsAriPaths;

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

    /**
     * Create a bridge; with `$bridgeId` the bridge gets that id (POST /bridges/{bridgeId}).
     */
    public function create(string $type = 'mixing', ?string $name = null, ?string $bridgeId = null): array
    {
        $uri = $bridgeId === null ? 'bridges' : 'bridges/'.$this->segment($bridgeId);

        return $this->client->post($uri, array_filter([
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
