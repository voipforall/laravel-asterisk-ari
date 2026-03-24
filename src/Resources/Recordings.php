<?php

namespace VoIPforAll\AsteriskAri\Resources;

use VoIPforAll\AsteriskAri\Contracts\AriClientInterface;

class Recordings
{
    public function __construct(
        private readonly AriClientInterface $client,
    ) {}

    public function listStored(): array
    {
        return $this->client->get('recordings/stored');
    }

    public function getStored(string $name): array
    {
        return $this->client->get("recordings/stored/{$name}");
    }

    public function deleteStored(string $name): array
    {
        return $this->client->delete("recordings/stored/{$name}");
    }

    public function getLive(string $name): array
    {
        return $this->client->get("recordings/live/{$name}");
    }

    public function stop(string $name): array
    {
        return $this->client->post("recordings/live/{$name}/stop");
    }

    public function pause(string $name): array
    {
        return $this->client->post("recordings/live/{$name}/pause");
    }

    public function unpause(string $name): array
    {
        return $this->client->delete("recordings/live/{$name}/pause");
    }
}
