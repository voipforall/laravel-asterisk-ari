<?php

namespace VoIPforAll\AsteriskAri\Resources;

use VoIPforAll\AsteriskAri\Contracts\AriClientInterface;

class Channels
{
    public function __construct(
        private readonly AriClientInterface $client,
    ) {}

    public function list(): array
    {
        return $this->client->get('channels');
    }

    public function get(string $channelId): array
    {
        return $this->client->get("channels/{$channelId}");
    }

    public function originate(
        string $endpoint,
        ?string $extension = null,
        ?string $context = null,
        ?string $callerId = null,
        ?int $timeout = null,
        ?string $app = null,
    ): array {
        return $this->client->post('channels', array_filter([
            'endpoint' => $endpoint,
            'extension' => $extension,
            'context' => $context,
            'callerId' => $callerId,
            'timeout' => $timeout,
            'app' => $app,
        ]));
    }

    public function hangup(string $channelId, string $reason = 'normal'): array
    {
        return $this->client->delete("channels/{$channelId}", [
            'reason_code' => $reason,
        ]);
    }

    public function answer(string $channelId): array
    {
        return $this->client->post("channels/{$channelId}/answer");
    }

    public function hold(string $channelId): array
    {
        return $this->client->post("channels/{$channelId}/hold");
    }

    public function unhold(string $channelId): array
    {
        return $this->client->delete("channels/{$channelId}/hold");
    }

    public function mute(string $channelId, string $direction = 'both'): array
    {
        return $this->client->post("channels/{$channelId}/mute", [
            'direction' => $direction,
        ]);
    }

    public function unmute(string $channelId, string $direction = 'both'): array
    {
        return $this->client->delete("channels/{$channelId}/mute", [
            'direction' => $direction,
        ]);
    }

    public function getVariable(string $channelId, string $variable): array
    {
        return $this->client->get("channels/{$channelId}/variable", [
            'variable' => $variable,
        ]);
    }

    public function setVariable(string $channelId, string $variable, string $value): array
    {
        return $this->client->post("channels/{$channelId}/variable", [
            'variable' => $variable,
            'value' => $value,
        ]);
    }

    public function sendDtmf(string $channelId, string $dtmf): array
    {
        return $this->client->post("channels/{$channelId}/dtmf", [
            'dtmf' => $dtmf,
        ]);
    }

    public function record(string $channelId, string $name, string $format = 'wav'): array
    {
        return $this->client->post("channels/{$channelId}/record", [
            'name' => $name,
            'format' => $format,
        ]);
    }
}
