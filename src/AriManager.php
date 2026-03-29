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
    private ?Endpoints $endpoints = null;

    private ?Channels $channels = null;

    private ?Bridges $bridges = null;

    private ?DeviceStates $deviceStates = null;

    private ?Recordings $recordings = null;

    private ?Asterisk $asterisk = null;

    public function __construct(
        private readonly AriClientInterface $client,
    ) {}

    public function endpoints(): Endpoints
    {
        return $this->endpoints ??= new Endpoints($this->client);
    }

    public function channels(): Channels
    {
        return $this->channels ??= new Channels($this->client);
    }

    public function bridges(): Bridges
    {
        return $this->bridges ??= new Bridges($this->client);
    }

    public function deviceStates(): DeviceStates
    {
        return $this->deviceStates ??= new DeviceStates($this->client);
    }

    public function recordings(): Recordings
    {
        return $this->recordings ??= new Recordings($this->client);
    }

    public function asterisk(): Asterisk
    {
        return $this->asterisk ??= new Asterisk($this->client);
    }
}
