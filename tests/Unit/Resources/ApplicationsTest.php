<?php

use VoIPforAll\AsteriskAri\Contracts\AriClientInterface;
use VoIPforAll\AsteriskAri\Resources\Applications;

beforeEach(function () {
    $this->client = Mockery::mock(AriClientInterface::class);
    $this->applications = new Applications($this->client);
});

it('lists applications', function () {
    $this->client->shouldReceive('get')
        ->with('applications')
        ->once()
        ->andReturn([['name' => 'voicebot']]);

    expect($this->applications->list())->toHaveCount(1);
});

it('gets an application', function () {
    $this->client->shouldReceive('get')
        ->with('applications/voicebot')
        ->once()
        ->andReturn(['name' => 'voicebot', 'channel_ids' => []]);

    expect($this->applications->get('voicebot'))->toHaveKey('name', 'voicebot');
});

it('subscribes an application to one event source', function () {
    $this->client->shouldReceive('post')
        ->with('applications/voicebot/subscription', ['eventSource' => 'channel:ch-1'])
        ->once()
        ->andReturn(['name' => 'voicebot']);

    $this->applications->subscribe('voicebot', 'channel:ch-1');
});

it('subscribes an application to several event sources', function () {
    $this->client->shouldReceive('post')
        ->with('applications/voicebot/subscription', ['eventSource' => ['channel:ch-1', 'bridge:br-1']])
        ->once()
        ->andReturn([]);

    $this->applications->subscribe('voicebot', ['channel:ch-1', 'bridge:br-1']);
});

it('unsubscribes an application from event sources', function () {
    $this->client->shouldReceive('delete')
        ->with('applications/voicebot/subscription', ['eventSource' => 'channel:ch-1,endpoint:PJSIP/1000'])
        ->once()
        ->andReturn([]);

    $this->applications->unsubscribe('voicebot', ['channel:ch-1', 'endpoint:PJSIP/1000']);
});

it('rejects an empty application name', function () {
    $this->client->shouldNotReceive('get');

    $this->applications->get('');
})->throws(InvalidArgumentException::class);
