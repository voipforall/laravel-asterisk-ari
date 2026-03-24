<?php

namespace VoIPforAll\AsteriskAri\Events;

class ChannelEnteredBridge extends AriEvent
{
    public function getBridge(): array
    {
        return $this->payload['bridge'] ?? [];
    }

    public function getChannel(): array
    {
        return $this->payload['channel'] ?? [];
    }
}
