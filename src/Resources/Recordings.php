<?php

namespace VoIPforAll\AsteriskAri\Resources;

use VoIPforAll\AsteriskAri\Concerns\BuildsAriPaths;
use VoIPforAll\AsteriskAri\Contracts\AriClientInterface;

class Recordings
{
    use BuildsAriPaths;

    public function __construct(
        private readonly AriClientInterface $client,
    ) {}

    public function listStored(): array
    {
        return $this->client->get('recordings/stored');
    }

    public function getStored(string $name): array
    {
        return $this->client->get("recordings/stored/{$this->recordingPath($name)}");
    }

    /**
     * Download a stored recording's audio as a binary string.
     *
     * @throws \InvalidArgumentException when the name is absolute or tries to traverse directories.
     */
    public function getStoredFile(string $name): string
    {
        return $this->client->getRaw("recordings/stored/{$this->recordingPath($name)}/file");
    }

    /**
     * Copy a stored recording to a new name.
     */
    public function copyStored(string $name, string $destinationName): array
    {
        $this->recordingPath($destinationName);

        return $this->client->post("recordings/stored/{$this->recordingPath($name)}/copy", [
            'destinationRecordingName' => $destinationName,
        ]);
    }

    public function deleteStored(string $name): array
    {
        return $this->client->delete("recordings/stored/{$this->recordingPath($name)}");
    }

    public function getLive(string $name): array
    {
        return $this->client->get("recordings/live/{$this->recordingPath($name)}");
    }

    public function stop(string $name): array
    {
        return $this->client->post("recordings/live/{$this->recordingPath($name)}/stop");
    }

    public function pause(string $name): array
    {
        return $this->client->post("recordings/live/{$this->recordingPath($name)}/pause");
    }

    public function unpause(string $name): array
    {
        return $this->client->delete("recordings/live/{$this->recordingPath($name)}/pause");
    }
}
