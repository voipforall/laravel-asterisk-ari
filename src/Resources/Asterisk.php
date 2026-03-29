<?php

namespace VoIPforAll\AsteriskAri\Resources;

use VoIPforAll\AsteriskAri\Contracts\AriClientInterface;
use VoIPforAll\AsteriskAri\Exceptions\AriConnectionException;
use VoIPforAll\AsteriskAri\Exceptions\AriException;

class Asterisk
{
    public function __construct(
        private readonly AriClientInterface $client,
    ) {}

    public function info(?string $only = null): array
    {
        return $this->client->get('asterisk/info', array_filter([
            'only' => $only,
        ], fn ($value) => $value !== null));
    }

    public function ping(): bool
    {
        try {
            $this->info('system');

            return true;
        } catch (AriException|AriConnectionException) {
            return false;
        }
    }

    public function getVariable(string $variable): array
    {
        return $this->client->get('asterisk/variable', [
            'variable' => $variable,
        ]);
    }
}
