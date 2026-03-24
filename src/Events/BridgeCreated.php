<?php

namespace VoIPforAll\AsteriskAri\Events;

class BridgeCreated extends AriEvent
{
    public function getBridge(): array
    {
        return $this->payload['bridge'] ?? [];
    }
}
