<?php

namespace VoIPforAll\AsteriskAri\Events;

class ChannelDestroyed extends AriEvent
{
    public function getChannel(): array
    {
        return $this->payload['channel'] ?? [];
    }

    public function getCause(): int
    {
        return $this->payload['cause'] ?? 0;
    }

    public function getCauseText(): string
    {
        return $this->payload['cause_txt'] ?? '';
    }
}
