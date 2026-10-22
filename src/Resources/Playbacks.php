<?php

namespace VoIPforAll\AsteriskAri\Resources;

use InvalidArgumentException;
use VoIPforAll\AsteriskAri\Concerns\BuildsAriPaths;
use VoIPforAll\AsteriskAri\Contracts\AriClientInterface;

class Playbacks
{
    use BuildsAriPaths;

    private const OPERATIONS = ['restart', 'pause', 'unpause', 'reverse', 'forward'];

    public function __construct(
        private readonly AriClientInterface $client,
    ) {}

    public function get(string $playbackId): array
    {
        return $this->client->get("playbacks/{$this->segment($playbackId)}");
    }

    public function stop(string $playbackId): array
    {
        return $this->client->delete("playbacks/{$this->segment($playbackId)}");
    }

    /**
     * Control a running playback.
     *
     * @param  string  $operation  restart, pause, unpause, reverse or forward
     *
     * @throws InvalidArgumentException when the operation is not supported by ARI.
     */
    public function control(string $playbackId, string $operation): array
    {
        if (! in_array($operation, self::OPERATIONS, true)) {
            throw new InvalidArgumentException("Invalid playback operation: {$operation}.");
        }

        return $this->client->post("playbacks/{$this->segment($playbackId)}/control", [
            'operation' => $operation,
        ]);
    }
}
