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

it('downloads a stored recording file as binary', function () {
    $this->client->shouldReceive('getRaw')
        ->with('recordings/stored/voicebot/turn-1/file')
        ->once()
        ->andReturn("RIFF\x00\x01binary");

    expect($this->recordings->getStoredFile('voicebot/turn-1'))->toBe("RIFF\x00\x01binary");
});

it('url-encodes each segment of a stored recording name', function () {
    $this->client->shouldReceive('getRaw')
        ->with('recordings/stored/my%20rec%3F/file')
        ->once()
        ->andReturn('');

    $this->recordings->getStoredFile('my rec?');
});

it('rejects recording names that traverse directories', function (string $name) {
    $this->client->shouldNotReceive('getRaw');

    $this->recordings->getStoredFile($name);
})->throws(InvalidArgumentException::class)->with([
    'parent' => ['../etc/passwd'],
    'nested parent' => ['voicebot/../../secret'],
    'absolute' => ['/var/spool/asterisk/recording/x'],
    'empty' => [''],
    'empty segment' => ['a//b'],
    'backslash' => ['..\\x'],
    'null byte' => ["rec\0.wav"],
]);

it('copies a stored recording', function () {
    $this->client->shouldReceive('post')
        ->with('recordings/stored/source/copy', ['destinationRecordingName' => 'archive/source'])
        ->once()
        ->andReturn(['name' => 'archive/source']);

    expect($this->recordings->copyStored('source', 'archive/source'))->toHaveKey('name', 'archive/source');
});

it('rejects a traversing destination name when copying', function () {
    $this->client->shouldNotReceive('post');

    $this->recordings->copyStored('source', '../outside');
})->throws(InvalidArgumentException::class);

it('rejects traversal in every stored and live recording method', function (string $method, string $name) {
    $this->client->shouldNotReceive('get', 'post', 'delete');

    $this->recordings->{$method}($name);
})->throws(InvalidArgumentException::class)->with([
    'deleteStored parent' => ['deleteStored', '../x'],
    'deleteStored absolute' => ['deleteStored', '/abs'],
    'deleteStored backslash' => ['deleteStored', 'a\\b'],
    'getStored parent' => ['getStored', 'voicebot/../../x'],
    'getLive parent' => ['getLive', '../x'],
    'stop parent' => ['stop', '../x'],
    'pause absolute' => ['pause', '/abs'],
    'unpause backslash' => ['unpause', 'a\\b'],
]);

it('encodes query and fragment characters in a stored recording name', function () {
    $this->client->shouldReceive('delete')
        ->with('recordings/stored/voicebot/turn%3F1%231')
        ->once()
        ->andReturn([]);

    $this->recordings->deleteStored('voicebot/turn?1#1');
});
