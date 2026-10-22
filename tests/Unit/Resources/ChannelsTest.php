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
        ->with('channels/ch-123', ['reason' => 'normal'])
        ->once()
        ->andReturn([]);

    $result = $this->channels->hangup('ch-123');

    expect($result)->toBeArray();
});

it('hangs up with custom reason', function () {
    $this->client->shouldReceive('delete')
        ->with('channels/ch-123', ['reason' => 'busy'])
        ->once()
        ->andReturn([]);

    $this->channels->hangup('ch-123', 'busy');
});

it('hangs up with a numeric cause code', function () {
    $this->client->shouldReceive('delete')
        ->with('channels/ch-123', ['reason_code' => '17'])
        ->once()
        ->andReturn([]);

    $this->channels->hangup('ch-123', '17');
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

it('originates into a Stasis app with args, channel id and variables', function () {
    $this->client->shouldReceive('post')
        ->with('channels', [
            'endpoint' => 'Local/100@internal',
            'app' => 'voicebot',
            'appArgs' => 'deployment,7',
            'channelId' => 'vb-123',
            'variables' => ['CALLERID(name)' => 'Bot'],
        ])
        ->once()
        ->andReturn(['id' => 'vb-123']);

    $result = $this->channels->originate(
        endpoint: 'Local/100@internal',
        app: 'voicebot',
        appArgs: 'deployment,7',
        channelId: 'vb-123',
        variables: ['CALLERID(name)' => 'Bot'],
    );

    expect($result)->toHaveKey('id', 'vb-123');
});

it('originates to a dialplan priority or label', function () {
    $this->client->shouldReceive('post')
        ->with('channels', [
            'endpoint' => 'PJSIP/1000',
            'extension' => '200',
            'context' => 'internal',
            'priority' => 1,
            'label' => 'start',
            'otherChannelId' => 'other-1',
        ])
        ->once()
        ->andReturn([]);

    $this->channels->originate(endpoint: 'PJSIP/1000', extension: '200', context: 'internal', priority: 1, label: 'start', otherChannelId: 'other-1');
});

it('records with options only when given', function () {
    $this->client->shouldReceive('post')
        ->with('channels/ch-123/record', [
            'name' => 'turn-1',
            'format' => 'wav',
            'maxDurationSeconds' => 30,
            'maxSilenceSeconds' => 2,
            'ifExists' => 'overwrite',
            'beep' => false,
            'terminateOn' => '#',
        ])
        ->once()
        ->andReturn(['name' => 'turn-1']);

    $this->channels->record('ch-123', 'turn-1', 'wav', maxDurationSeconds: 30, maxSilenceSeconds: 2, ifExists: 'overwrite', beep: false, terminateOn: '#');
});

it('plays a single media on a channel', function () {
    $this->client->shouldReceive('post')
        ->with('channels/ch-123/play', ['media' => 'sound:hello-world'])
        ->once()
        ->andReturn(['id' => 'pb-1']);

    expect($this->channels->play('ch-123', 'sound:hello-world'))->toHaveKey('id', 'pb-1');
});

it('plays several media with a playback id and language', function () {
    $this->client->shouldReceive('post')
        ->with('channels/ch-123/play/pb-9', [
            'media' => ['sound:hello-world', 'digits:123'],
            'lang' => 'pt_BR',
            'offsetms' => 0,
            'skipms' => 3000,
        ])
        ->once()
        ->andReturn(['id' => 'pb-9']);

    $this->channels->play('ch-123', ['sound:hello-world', 'digits:123'], 'pb-9', 'pt_BR', 0, 3000);
});

it('encodes channel ids used in new path segments', function () {
    $this->client->shouldReceive('post')
        ->with('channels/a%2F..%2Fb/play', ['media' => 'sound:beep'])
        ->once()
        ->andReturn([]);

    $this->channels->play('a/../b', 'sound:beep');
});

it('rejects a dot-segment channel id', function () {
    $this->client->shouldNotReceive('post');

    $this->channels->play('..', 'sound:beep');
})->throws(InvalidArgumentException::class);

it('continues a channel in the dialplan', function () {
    $this->client->shouldReceive('post')
        ->with('channels/ch-123/continue', [])
        ->once()
        ->andReturn([]);

    $this->channels->continueInDialplan('ch-123');
});

it('continues a channel at a new dialplan location', function () {
    $this->client->shouldReceive('post')
        ->with('channels/ch-123/continue', ['context' => 'internal', 'extension' => '200', 'priority' => 1])
        ->once()
        ->andReturn([]);

    $this->channels->continueInDialplan('ch-123', 'internal', '200', 1);
});

it('continues a channel to a label', function () {
    $this->client->shouldReceive('post')
        ->with('channels/ch-123/continue', ['label' => 'menu'])
        ->once()
        ->andReturn([]);

    $this->channels->continueInDialplan('ch-123', label: 'menu');
});

it('redirects a channel', function () {
    $this->client->shouldReceive('post')
        ->with('channels/ch-123/redirect', ['endpoint' => 'PJSIP/1000'])
        ->once()
        ->andReturn([]);

    $this->channels->redirect('ch-123', 'PJSIP/1000');
});

it('snoops a channel without whisper by default', function () {
    $this->client->shouldReceive('post')
        ->with('channels/ch-123/snoop', ['app' => 'sentiment', 'spy' => 'both', 'whisper' => 'none'])
        ->once()
        ->andReturn(['id' => 'snoop-1']);

    expect($this->channels->snoop('ch-123', 'sentiment'))->toHaveKey('id', 'snoop-1');
});

it('snoops a channel with a snoop id and app args', function () {
    $this->client->shouldReceive('post')
        ->with('channels/ch-123/snoop/snoop-7', ['app' => 'sentiment', 'spy' => 'in', 'whisper' => 'none', 'appArgs' => 'call,42'])
        ->once()
        ->andReturn([]);

    $this->channels->snoop('ch-123', 'sentiment', spy: 'in', appArgs: 'call,42', snoopId: 'snoop-7');
});

it('rejects invalid snoop directions', function (string $spy, string $whisper) {
    $this->client->shouldNotReceive('post');

    $this->channels->snoop('ch-123', 'app', $spy, $whisper);
})->throws(InvalidArgumentException::class)->with([
    'bad spy' => ['sideways', 'none'],
    'bad whisper' => ['in', 'loud'],
]);

it('creates an RTP external media channel', function () {
    $this->client->shouldReceive('post')
        ->with('channels/externalMedia', [
            'app' => 'voicebot',
            'format' => 'slin16',
            'external_host' => '127.0.0.1:40000',
            'channelId' => 'em-1',
            'encapsulation' => 'rtp',
            'transport' => 'udp',
        ])
        ->once()
        ->andReturn(['id' => 'em-1']);

    $this->channels->externalMedia('voicebot', 'slin16', '127.0.0.1:40000', 'em-1');
});

it('creates a server websocket external media channel with options', function () {
    $this->client->shouldReceive('post')
        ->with('channels/externalMedia', [
            'app' => 'voicebot',
            'format' => 'ulaw',
            'encapsulation' => 'none',
            'transport' => 'websocket',
            'connection_type' => 'server',
            'direction' => 'both',
            'variables' => ['VB' => '1'],
            'data' => 'abc',
            'transport_data' => 'f(json)',
        ])
        ->once()
        ->andReturn([]);

    $this->channels->externalMedia(
        app: 'voicebot',
        format: 'ulaw',
        encapsulation: 'none',
        transport: 'websocket',
        connectionType: 'server',
        direction: 'both',
        variables: ['VB' => '1'],
        data: 'abc',
        transportData: 'f(json)',
    );
});

it('rejects an invalid recording name before recording', function (string $name) {
    $this->client->shouldNotReceive('post');

    $this->channels->record('ch-123', $name);
})->throws(InvalidArgumentException::class)->with([
    'parent' => ['../x'],
    'absolute' => ['/abs'],
    'backslash' => ['a\\b'],
]);

it('sends the raw recording name with sub-directories when recording', function () {
    $this->client->shouldReceive('post')
        ->with('channels/ch-123/record', ['name' => 'voicebot/turn-1', 'format' => 'wav'])
        ->once()
        ->andReturn([]);

    $this->channels->record('ch-123', 'voicebot/turn-1');
});

it('encodes the channel id in every channel method', function () {
    $this->client->shouldReceive('delete')
        ->with('channels/a%2F..%2Fb', ['reason' => 'normal'])
        ->once()
        ->andReturn([]);

    $this->channels->hangup('a/../b');
});

it('rejects a dot-segment channel id on hangup', function () {
    $this->client->shouldNotReceive('delete');

    $this->channels->hangup('..');
})->throws(InvalidArgumentException::class);
