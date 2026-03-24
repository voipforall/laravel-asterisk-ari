<?php

namespace VoIPforAll\AsteriskAri\Events;

class ChannelStateChange extends AriEvent
{
    public function getChannel(): array
    {
        return $this->payload['channel'] ?? [];
    }
}
