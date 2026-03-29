<?php

use VoIPforAll\AsteriskAri\Contracts\AriClientInterface;
use VoIPforAll\AsteriskAri\Resources\Recordings;

beforeEach(function () {
    $this->client = Mockery::mock(AriClientInterface::class);
    $this->recordings = new Recordings($this->client);
});

it('lists stored recordings', function () {
    $this->client->shouldReceive('get')
        ->with('recordings/stored')
        ->once()
        ->andReturn([['name' => 'rec1'], ['name' => 'rec2']]);

    $result = $this->recordings->listStored();

    expect($result)->toHaveCount(2);
});

it('gets a stored recording', function () {
    $this->client->shouldReceive('get')
        ->with('recordings/stored/my-recording')
        ->once()
        ->andReturn(['name' => 'my-recording', 'format' => 'wav']);

    $result = $this->recordings->getStored('my-recording');

    expect($result)->toHaveKey('name', 'my-recording');
});

it('deletes a stored recording', function () {
    $this->client->shouldReceive('delete')
        ->with('recordings/stored/my-recording')
        ->once()
        ->andReturn([]);

    $this->recordings->deleteStored('my-recording');
});

it('gets a live recording', function () {
    $this->client->shouldReceive('get')
        ->with('recordings/live/active-rec')
        ->once()
        ->andReturn(['name' => 'active-rec', 'state' => 'recording']);

    $result = $this->recordings->getLive('active-rec');

    expect($result)->toHaveKey('state', 'recording');
});

it('stops a live recording', function () {
    $this->client->shouldReceive('post')
        ->with('recordings/live/active-rec/stop')
        ->once()
        ->andReturn([]);

    $this->recordings->stop('active-rec');
});

it('pauses a live recording', function () {
    $this->client->shouldReceive('post')
        ->with('recordings/live/active-rec/pause')
        ->once()
        ->andReturn([]);

    $this->recordings->pause('active-rec');
});

it('unpauses a live recording', function () {
    $this->client->shouldReceive('delete')
        ->with('recordings/live/active-rec/pause')
        ->once()
        ->andReturn([]);

    $this->recordings->unpause('active-rec');
});
