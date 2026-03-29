<?php

use VoIPforAll\AsteriskAri\Contracts\AriClientInterface;
use VoIPforAll\AsteriskAri\Resources\Endpoints;

beforeEach(function () {
    $this->client = Mockery::mock(AriClientInterface::class);
    $this->endpoints = new Endpoints($this->client);
});

it('lists all endpoints', function () {
    $this->client->shouldReceive('get')
        ->with('endpoints')
        ->once()
        ->andReturn([['technology' => 'PJSIP', 'resource' => '1000']]);

    $result = $this->endpoints->list();

    expect($result)->toHaveCount(1);
});

it('gets a specific endpoint', function () {
    $this->client->shouldReceive('get')
        ->with('endpoints/PJSIP/1000')
        ->once()
        ->andReturn(['technology' => 'PJSIP', 'resource' => '1000', 'state' => 'online']);

    $result = $this->endpoints->get('PJSIP', '1000');

    expect($result)->toHaveKey('state', 'online');
});

it('lists endpoints by technology', function () {
    $this->client->shouldReceive('get')
        ->with('endpoints/PJSIP')
        ->once()
        ->andReturn([
            ['resource' => '1000'],
            ['resource' => '1001'],
        ]);

    $result = $this->endpoints->listByTechnology('PJSIP');

    expect($result)->toHaveCount(2);
});
