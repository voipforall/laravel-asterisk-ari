<?php

namespace VoIPforAll\AsteriskAri\Events;

class DeviceStateChanged extends AriEvent
{
    public function getDeviceState(): array
    {
        return $this->payload['device_state'] ?? [];
    }
}
