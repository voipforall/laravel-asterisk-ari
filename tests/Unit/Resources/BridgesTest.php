<?php

use VoIPforAll\AsteriskAri\Contracts\AriClientInterface;
use VoIPforAll\AsteriskAri\Resources\Bridges;

beforeEach(function () {
    $this->client = Mockery::mock(AriClientInterface::class);
    $this->bridges = new Bridges($this->client);
});

it('lists all bridges', function () {
    $this->client->shouldReceive('get')
        ->with('bridges')
        ->once()
        ->andReturn([['id' => 'br1'], ['id' => 'br2']]);

    $result = $this->bridges->list();

    expect($result)->toHaveCount(2);
});

it('gets a single bridge', function () {
    $this->client->shouldReceive('get')
        ->with('bridges/br-123')
        ->once()
        ->andReturn(['id' => 'br-123', 'bridge_type' => 'mixing']);

    $result = $this->bridges->get('br-123');

    expect($result)->toHaveKey('id', 'br-123');
});

it('creates a bridge with defaults', function () {
    $this->client->shouldReceive('post')
        ->with('bridges', ['type' => 'mixing'])
        ->once()
        ->andReturn(['id' => 'br-new']);

    $result = $this->bridges->create();

    expect($result)->toHaveKey('id', 'br-new');
});

it('creates a bridge with custom type and name', function () {
    $this->client->shouldReceive('post')
        ->with('bridges', ['type' => 'holding', 'name' => 'my-bridge'])
        ->once()
        ->andReturn(['id' => 'br-new', 'name' => 'my-bridge']);

    $result = $this->bridges->create('holding', 'my-bridge');

    expect($result)->toHaveKey('name', 'my-bridge');
});

it('destroys a bridge', function () {
    $this->client->shouldReceive('delete')
        ->with('bridges/br-123')
        ->once()
        ->andReturn([]);

    $this->bridges->destroy('br-123');
});

it('adds a single channel to a bridge', function () {
    $this->client->shouldReceive('post')
        ->with('bridges/br-123/addChannel', ['channel' => 'ch-1'])
        ->once()
        ->andReturn([]);

    $this->bridges->addChannel('br-123', 'ch-1');
});

it('adds multiple channels to a bridge', function () {
    $this->client->shouldReceive('post')
        ->with('bridges/br-123/addChannel', ['channel' => 'ch-1,ch-2,ch-3'])
        ->once()
        ->andReturn([]);

    $this->bridges->addChannel('br-123', ['ch-1', 'ch-2', 'ch-3']);
});

it('removes a single channel from a bridge', function () {
    $this->client->shouldReceive('post')
        ->with('bridges/br-123/removeChannel', ['channel' => 'ch-1'])
        ->once()
        ->andReturn([]);

    $this->bridges->removeChannel('br-123', 'ch-1');
});

it('removes multiple channels from a bridge', function () {
    $this->client->shouldReceive('post')
        ->with('bridges/br-123/removeChannel', ['channel' => 'ch-1,ch-2'])
        ->once()
        ->andReturn([]);

    $this->bridges->removeChannel('br-123', ['ch-1', 'ch-2']);
});
