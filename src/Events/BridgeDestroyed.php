<?php

namespace VoIPforAll\AsteriskAri\Events;

class BridgeDestroyed extends AriEvent
{
    public function getBridge(): array
    {
        return $this->payload['bridge'] ?? [];
    }
}
