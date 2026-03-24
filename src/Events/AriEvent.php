<?php

namespace VoIPforAll\AsteriskAri\Events;

use Illuminate\Foundation\Events\Dispatchable;

abstract class AriEvent
{
    use Dispatchable;

    public function __construct(
        public readonly array $payload,
    ) {}

    public function getType(): string
    {
        return $this->payload['type'] ?? 'Unknown';
    }

    public function getTimestamp(): ?string
    {
        return $this->payload['timestamp'] ?? null;
    }

    public function getApplication(): ?string
    {
        return $this->payload['application'] ?? null;
    }
}
