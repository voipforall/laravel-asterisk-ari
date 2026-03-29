<?php

use VoIPforAll\AsteriskAri\Contracts\AriClientInterface;
use VoIPforAll\AsteriskAri\Resources\DeviceStates;

beforeEach(function () {
    $this->client = Mockery::mock(AriClientInterface::class);
    $this->deviceStates = new DeviceStates($this->client);
});

it('lists all device states', function () {
    $this->client->shouldReceive('get')
        ->with('deviceStates')
        ->once()
        ->andReturn([['name' => 'Custom:dev1', 'state' => 'NOT_INUSE']]);

    $result = $this->deviceStates->list();

    expect($result)->toHaveCount(1);
});

it('gets a device state', function () {
    $this->client->shouldReceive('get')
        ->with('deviceStates/Custom:dev1')
        ->once()
        ->andReturn(['name' => 'Custom:dev1', 'state' => 'INUSE']);

    $result = $this->deviceStates->get('Custom:dev1');

    expect($result)->toHaveKey('state', 'INUSE');
});

it('updates a device state', function () {
    $this->client->shouldReceive('put')
        ->with('deviceStates/Custom:dev1', ['deviceState' => 'BUSY'])
        ->once()
        ->andReturn([]);

    $this->deviceStates->update('Custom:dev1', 'BUSY');
});

it('deletes a device state', function () {
    $this->client->shouldReceive('delete')
        ->with('deviceStates/Custom:dev1')
        ->once()
        ->andReturn([]);

    $this->deviceStates->delete('Custom:dev1');
});
