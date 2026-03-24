<?php

namespace VoIPforAll\AsteriskAri\Resources;

use VoIPforAll\AsteriskAri\Contracts\AriClientInterface;

class Endpoints
{
    public function __construct(
        private readonly AriClientInterface $client,
    ) {}

    public function list(): array
    {
        return $this->client->get('endpoints');
    }

    public function get(string $technology, string $resource): array
    {
        return $this->client->get("endpoints/{$technology}/{$resource}");
    }

    public function listByTechnology(string $technology): array
    {
        return $this->client->get("endpoints/{$technology}");
    }
}
