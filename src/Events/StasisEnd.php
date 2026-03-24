<?php

namespace VoIPforAll\AsteriskAri\Events;

class StasisEnd extends AriEvent
{
    public function getChannel(): array
    {
        return $this->payload['channel'] ?? [];
    }
}
