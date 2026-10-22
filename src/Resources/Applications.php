<?php

namespace VoIPforAll\AsteriskAri\Resources;

use VoIPforAll\AsteriskAri\Concerns\BuildsAriPaths;
use VoIPforAll\AsteriskAri\Contracts\AriClientInterface;

class Applications
{
    use BuildsAriPaths;

    public function __construct(
        private readonly AriClientInterface $client,
    ) {}

    public function list(): array
    {
        return $this->client->get('applications');
    }

    public function get(string $app): array
    {
        return $this->client->get("applications/{$this->segment($app)}");
    }

    /**
     * Subscribe an application to event sources (channel:{id}, bridge:{id}, endpoint:{tech}/{resource}, deviceState:{name}).
     *
     * @param  string|array<int, string>  $eventSource
     */
    public function subscribe(string $app, string|array $eventSource): array
    {
        return $this->client->post("applications/{$this->segment($app)}/subscription", [
            'eventSource' => is_array($eventSource) ? array_values($eventSource) : $eventSource,
        ]);
    }

    /**
     * Unsubscribe an application from event sources.
     *
     * @param  string|array<int, string>  $eventSource
     */
    public function unsubscribe(string $app, string|array $eventSource): array
    {
        return $this->client->delete("applications/{$this->segment($app)}/subscription", [
            'eventSource' => $this->commaList($eventSource),
        ]);
    }
}
