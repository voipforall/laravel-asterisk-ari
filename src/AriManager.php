<?php

namespace VoIPforAll\AsteriskAri;

use VoIPforAll\AsteriskAri\Contracts\AriClientInterface;
use VoIPforAll\AsteriskAri\Resources\Asterisk;
use VoIPforAll\AsteriskAri\Resources\Bridges;
use VoIPforAll\AsteriskAri\Resources\Channels;
use VoIPforAll\AsteriskAri\Resources\DeviceStates;
use VoIPforAll\AsteriskAri\Resources\Endpoints;
use VoIPforAll\AsteriskAri\Resources\Recordings;

class AriManager
{
    public function __construct(
        private readonly AriClientInterface $client,
    ) {}

    public function endpoints(): Endpoints
    {
        return new Endpoints($this->client);
    }

    public function channels(): Channels
    {
        return new Channels($this->client);
    }

    public function bridges(): Bridges
    {
        return new Bridges($this->client);
    }

    public function deviceStates(): DeviceStates
    {
        return new DeviceStates($this->client);
    }

    public function recordings(): Recordings
    {
        return new Recordings($this->client);
    }

    public function asterisk(): Asterisk
    {
        return new Asterisk($this->client);
    }
}
