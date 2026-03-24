<?php

namespace VoIPforAll\AsteriskAri\Resources;

use VoIPforAll\AsteriskAri\Contracts\AriClientInterface;

class Asterisk
{
    public function __construct(
        private readonly AriClientInterface $client,
    ) {}

    public function info(?string $only = null): array
    {
        return $this->client->get('asterisk/info', array_filter([
            'only' => $only,
        ]));
    }

    public function ping(): bool
    {
        try {
            $this->info('system');

            return true;
        } catch (\Throwable) {
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
