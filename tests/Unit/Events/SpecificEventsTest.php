<?php

use VoIPforAll\AsteriskAri\Events\BridgeCreated;
use VoIPforAll\AsteriskAri\Events\ChannelCreated;
use VoIPforAll\AsteriskAri\Events\ChannelEnteredBridge;
use VoIPforAll\AsteriskAri\Events\StasisStart;

it('StasisStart returns channel data', function () {
    $event = new StasisStart([
        'type' => 'StasisStart',
        'channel' => ['id' => 'ch-1', 'state' => 'Ring'],
    ]);

    expect($event->getChannel())
        ->toHaveKey('id', 'ch-1')
        ->toHaveKey('state', 'Ring');
});

it('StasisStart returns args', function () {
    $event = new StasisStart([
        'type' => 'StasisStart',
        'args' => ['dialed', '1001'],
    ]);

    expect($event->getArgs())->toBe(['dialed', '1001']);
});

it('StasisStart returns empty array when channel is missing', function () {
    $event = new StasisStart(['type' => 'StasisStart']);

    expect($event->getChannel())->toBe([]);
    expect($event->getArgs())->toBe([]);
});

it('ChannelCreated returns channel data', function () {
    $event = new ChannelCreated([
        'type' => 'ChannelCreated',
        'channel' => ['id' => 'ch-2', 'name' => 'PJSIP/1000-00000001'],
    ]);

    expect($event->getChannel())->toHaveKey('id', 'ch-2');
});

it('BridgeCreated returns bridge data', function () {
    $event = new BridgeCreated([
        'type' => 'BridgeCreated',
        'bridge' => ['id' => 'br-1', 'bridge_type' => 'mixing'],
    ]);

    expect($event->getBridge())->toHaveKey('id', 'br-1');
});

it('ChannelEnteredBridge returns both channel and bridge', function () {
    $event = new ChannelEnteredBridge([
        'type' => 'ChannelEnteredBridge',
        'channel' => ['id' => 'ch-1'],
        'bridge' => ['id' => 'br-1'],
    ]);

    expect($event->getChannel())->toHaveKey('id', 'ch-1');
    expect($event->getBridge())->toHaveKey('id', 'br-1');
});
