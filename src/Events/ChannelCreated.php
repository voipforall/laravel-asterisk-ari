<?php

namespace VoIPforAll\AsteriskAri\Events;

class ChannelCreated extends AriEvent
{
    public function getChannel(): array
    {
        return $this->payload['channel'] ?? [];
    }
}
