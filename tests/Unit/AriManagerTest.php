<?php

use VoIPforAll\AsteriskAri\AriManager;
use VoIPforAll\AsteriskAri\Contracts\AriClientInterface;
use VoIPforAll\AsteriskAri\Resources\Asterisk;
use VoIPforAll\AsteriskAri\Resources\Bridges;
use VoIPforAll\AsteriskAri\Resources\Channels;
use VoIPforAll\AsteriskAri\Resources\DeviceStates;
use VoIPforAll\AsteriskAri\Resources\Endpoints;
use VoIPforAll\AsteriskAri\Resources\Recordings;

beforeEach(function () {
    $this->client = Mockery::mock(AriClientInterface::class);
    $this->manager = new AriManager($this->client);
});

it('returns an Endpoints resource', function () {
    expect($this->manager->endpoints())->toBeInstanceOf(Endpoints::class);
});

it('returns a Channels resource', function () {
    expect($this->manager->channels())->toBeInstanceOf(Channels::class);
});

it('returns a Bridges resource', function () {
    expect($this->manager->bridges())->toBeInstanceOf(Bridges::class);
});

it('returns a DeviceStates resource', function () {
    expect($this->manager->deviceStates())->toBeInstanceOf(DeviceStates::class);
});

it('returns a Recordings resource', function () {
    expect($this->manager->recordings())->toBeInstanceOf(Recordings::class);
});

it('returns an Asterisk resource', function () {
    expect($this->manager->asterisk())->toBeInstanceOf(Asterisk::class);
});

it('returns the same instance on repeated calls', function () {
    $first = $this->manager->channels();
    $second = $this->manager->channels();

    expect($first)->toBe($second);
});

it('returns different instances for different resources', function () {
    $channels = $this->manager->channels();
    $bridges = $this->manager->bridges();

    expect($channels)->not->toBe($bridges);
});
