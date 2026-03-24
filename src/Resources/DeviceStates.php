<?php

namespace VoIPforAll\AsteriskAri\Resources;

use VoIPforAll\AsteriskAri\Contracts\AriClientInterface;

class DeviceStates
{
    public function __construct(
        private readonly AriClientInterface $client,
    ) {}

    public function list(): array
    {
        return $this->client->get('deviceStates');
    }

    public function get(string $deviceName): array
    {
        return $this->client->get("deviceStates/{$deviceName}");
    }

    public function update(string $deviceName, string $state): array
    {
        return $this->client->put("deviceStates/{$deviceName}", [
            'deviceState' => $state,
        ]);
    }

    public function delete(string $deviceName): array
    {
        return $this->client->delete("deviceStates/{$deviceName}");
    }
}
