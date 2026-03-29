<?php

use VoIPforAll\AsteriskAri\Contracts\AriClientInterface;
use VoIPforAll\AsteriskAri\Exceptions\AriConnectionException;
use VoIPforAll\AsteriskAri\Resources\Asterisk;

beforeEach(function () {
    $this->client = Mockery::mock(AriClientInterface::class);
    $this->asterisk = new Asterisk($this->client);
});

it('gets full asterisk info', function () {
    $this->client->shouldReceive('get')
        ->with('asterisk/info', [])
        ->once()
        ->andReturn(['build' => [], 'system' => [], 'config' => []]);

    $result = $this->asterisk->info();

    expect($result)->toHaveKeys(['build', 'system', 'config']);
});

it('gets filtered asterisk info', function () {
    $this->client->shouldReceive('get')
        ->with('asterisk/info', ['only' => 'system'])
        ->once()
        ->andReturn(['system' => ['entity_id' => 'abc']]);

    $result = $this->asterisk->info('system');

    expect($result)->toHaveKey('system');
});

it('pings successfully', function () {
    $this->client->shouldReceive('get')
        ->with('asterisk/info', ['only' => 'system'])
        ->once()
        ->andReturn(['system' => []]);

    expect($this->asterisk->ping())->toBeTrue();
});

it('ping returns false on failure', function () {
    $this->client->shouldReceive('get')
        ->with('asterisk/info', ['only' => 'system'])
        ->once()
        ->andThrow(new AriConnectionException('Connection refused'));

    expect($this->asterisk->ping())->toBeFalse();
});

it('gets a global variable', function () {
    $this->client->shouldReceive('get')
        ->with('asterisk/variable', ['variable' => 'GLOBAL_VAR'])
        ->once()
        ->andReturn(['value' => 'hello']);

    $result = $this->asterisk->getVariable('GLOBAL_VAR');

    expect($result)->toHaveKey('value', 'hello');
});
