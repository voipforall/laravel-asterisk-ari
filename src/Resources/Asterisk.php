<?php

namespace VoIPforAll\AsteriskAri\Resources;

use VoIPforAll\AsteriskAri\Concerns\BuildsAriPaths;
use VoIPforAll\AsteriskAri\Contracts\AriClientInterface;
use VoIPforAll\AsteriskAri\Exceptions\AriConnectionException;
use VoIPforAll\AsteriskAri\Exceptions\AriException;

class Asterisk
{
    use BuildsAriPaths;

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

    public function setVariable(string $variable, string $value): array
    {
        return $this->client->post('asterisk/variable', [
            'variable' => $variable,
            'value' => $value,
        ]);
    }

    public function listModules(): array
    {
        return $this->client->get('asterisk/modules');
    }

    public function getModule(string $module): array
    {
        return $this->client->get("asterisk/modules/{$this->segment($module)}");
    }

    public function loadModule(string $module): array
    {
        return $this->client->post("asterisk/modules/{$this->segment($module)}");
    }

    public function unloadModule(string $module): array
    {
        return $this->client->delete("asterisk/modules/{$this->segment($module)}");
    }

    public function reloadModule(string $module): array
    {
        return $this->client->put("asterisk/modules/{$this->segment($module)}");
    }
}
