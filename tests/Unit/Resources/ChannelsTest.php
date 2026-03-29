<?php

use VoIPforAll\AsteriskAri\Contracts\AriClientInterface;
use VoIPforAll\AsteriskAri\Resources\Channels;

beforeEach(function () {
    $this->client = Mockery::mock(AriClientInterface::class);
    $this->channels = new Channels($this->client);
});

it('lists all channels', function () {
    $this->client->shouldReceive('get')
        ->with('channels')
        ->once()
        ->andReturn([['id' => 'ch1'], ['id' => 'ch2']]);

    $result = $this->channels->list();

    expect($result)->toHaveCount(2);
});

it('gets a single channel', function () {
    $this->client->shouldReceive('get')
        ->with('channels/ch-123')
        ->once()
        ->andReturn(['id' => 'ch-123', 'state' => 'Up']);

    $result = $this->channels->get('ch-123');

    expect($result)
        ->toHaveKey('id', 'ch-123')
        ->toHaveKey('state', 'Up');
});

it('originates a call with all parameters', function () {
    $this->client->shouldReceive('post')
        ->with('channels', [
            'endpoint' => 'PJSIP/1000',
            'extension' => '1001',
            'context' => 'default',
            'callerId' => '"Test" <1000>',
            'timeout' => 30,
            'app' => 'laravel',
        ])
        ->once()
        ->andReturn(['id' => 'ch-new']);

    $result = $this->channels->originate(
        endpoint: 'PJSIP/1000',
        extension: '1001',
        context: 'default',
        callerId: '"Test" <1000>',
        timeout: 30,
        app: 'laravel',
    );

    expect($result)->toHaveKey('id', 'ch-new');
});

it('originates a call with only required parameters', function () {
    $this->client->shouldReceive('post')
        ->with('channels', ['endpoint' => 'PJSIP/1000'])
        ->once()
        ->andReturn(['id' => 'ch-new']);

    $result = $this->channels->originate(endpoint: 'PJSIP/1000');

    expect($result)->toHaveKey('id');
});

it('does not strip falsy values when originating', function () {
    $this->client->shouldReceive('post')
        ->with('channels', [
            'endpoint' => 'PJSIP/1000',
            'timeout' => 0,
        ])
        ->once()
        ->andReturn(['id' => 'ch-new']);

    $result = $this->channels->originate(endpoint: 'PJSIP/1000', timeout: 0);

    expect($result)->toHaveKey('id');
});

it('hangs up a channel', function () {
    $this->client->shouldReceive('delete')
        ->with('channels/ch-123', ['reason_code' => 'normal'])
        ->once()
        ->andReturn([]);

    $result = $this->channels->hangup('ch-123');

    expect($result)->toBeArray();
});

it('hangs up with custom reason', function () {
    $this->client->shouldReceive('delete')
        ->with('channels/ch-123', ['reason_code' => 'busy'])
        ->once()
        ->andReturn([]);

    $this->channels->hangup('ch-123', 'busy');
});

it('answers a channel', function () {
    $this->client->shouldReceive('post')
        ->with('channels/ch-123/answer')
        ->once()
        ->andReturn([]);

    $this->channels->answer('ch-123');
});

it('holds and unholds a channel', function () {
    $this->client->shouldReceive('post')
        ->with('channels/ch-123/hold')
        ->once()
        ->andReturn([]);

    $this->client->shouldReceive('delete')
        ->with('channels/ch-123/hold')
        ->once()
        ->andReturn([]);

    $this->channels->hold('ch-123');
    $this->channels->unhold('ch-123');
});

it('mutes a channel with direction', function () {
    $this->client->shouldReceive('post')
        ->with('channels/ch-123/mute', ['direction' => 'in'])
        ->once()
        ->andReturn([]);

    $this->channels->mute('ch-123', 'in');
});

it('unmutes a channel', function () {
    $this->client->shouldReceive('delete')
        ->with('channels/ch-123/mute', ['direction' => 'both'])
        ->once()
        ->andReturn([]);

    $this->channels->unmute('ch-123');
});

it('gets a channel variable', function () {
    $this->client->shouldReceive('get')
        ->with('channels/ch-123/variable', ['variable' => 'CALLERID(num)'])
        ->once()
        ->andReturn(['value' => '1000']);

    $result = $this->channels->getVariable('ch-123', 'CALLERID(num)');

    expect($result)->toHaveKey('value', '1000');
});

it('sets a channel variable', function () {
    $this->client->shouldReceive('post')
        ->with('channels/ch-123/variable', ['variable' => 'MY_VAR', 'value' => 'test'])
        ->once()
        ->andReturn([]);

    $this->channels->setVariable('ch-123', 'MY_VAR', 'test');
});

it('sends DTMF', function () {
    $this->client->shouldReceive('post')
        ->with('channels/ch-123/dtmf', ['dtmf' => '1234#'])
        ->once()
        ->andReturn([]);

    $this->channels->sendDtmf('ch-123', '1234#');
});

it('records a channel', function () {
    $this->client->shouldReceive('post')
        ->with('channels/ch-123/record', ['name' => 'my-recording', 'format' => 'wav'])
        ->once()
        ->andReturn(['name' => 'my-recording']);

    $result = $this->channels->record('ch-123', 'my-recording');

    expect($result)->toHaveKey('name', 'my-recording');
});

it('records a channel with custom format', function () {
    $this->client->shouldReceive('post')
        ->with('channels/ch-123/record', ['name' => 'rec', 'format' => 'gsm'])
        ->once()
        ->andReturn([]);

    $this->channels->record('ch-123', 'rec', 'gsm');
});
