<?php

namespace VoIPforAll\AsteriskAri\Events;

class StasisStart extends AriEvent
{
    public function getChannel(): array
    {
        return $this->payload['channel'] ?? [];
    }

    public function getArgs(): array
    {
        return $this->payload['args'] ?? [];
    }
}
