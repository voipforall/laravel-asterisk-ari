<?php

use VoIPforAll\AsteriskAri\Contracts\AriClientInterface;
use VoIPforAll\AsteriskAri\Resources\Playbacks;

beforeEach(function () {
    $this->client = Mockery::mock(AriClientInterface::class);
    $this->playbacks = new Playbacks($this->client);
});

it('gets a playback', function () {
    $this->client->shouldReceive('get')
        ->with('playbacks/pb-1')
        ->once()
        ->andReturn(['id' => 'pb-1', 'state' => 'playing']);

    expect($this->playbacks->get('pb-1'))->toHaveKey('state', 'playing');
});

it('stops a playback', function () {
    $this->client->shouldReceive('delete')
        ->with('playbacks/pb-1')
        ->once()
        ->andReturn([]);

    $this->playbacks->stop('pb-1');
});

it('controls a playback', function (string $operation) {
    $this->client->shouldReceive('post')
        ->with('playbacks/pb-1/control', ['operation' => $operation])
        ->once()
        ->andReturn([]);

    $this->playbacks->control('pb-1', $operation);
})->with(['restart', 'pause', 'unpause', 'reverse', 'forward']);

it('rejects an unknown playback operation', function () {
    $this->client->shouldNotReceive('post');

    $this->playbacks->control('pb-1', 'rewind');
})->throws(InvalidArgumentException::class);

it('encodes playback ids', function () {
    $this->client->shouldReceive('get')
        ->with('playbacks/a%2Fb')
        ->once()
        ->andReturn([]);

    $this->playbacks->get('a/b');
});
