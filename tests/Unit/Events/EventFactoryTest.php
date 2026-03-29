<?php

use VoIPforAll\AsteriskAri\Events\AriEvent;
use VoIPforAll\AsteriskAri\Events\BridgeCreated;
use VoIPforAll\AsteriskAri\Events\BridgeDestroyed;
use VoIPforAll\AsteriskAri\Events\ChannelCreated;
use VoIPforAll\AsteriskAri\Events\ChannelDestroyed;
use VoIPforAll\AsteriskAri\Events\ChannelEnteredBridge;
use VoIPforAll\AsteriskAri\Events\ChannelLeftBridge;
use VoIPforAll\AsteriskAri\Events\ChannelStateChange;
use VoIPforAll\AsteriskAri\Events\DeviceStateChanged;
use VoIPforAll\AsteriskAri\Events\EventFactory;
use VoIPforAll\AsteriskAri\Events\StasisEnd;
use VoIPforAll\AsteriskAri\Events\StasisStart;

it('creates the correct event class for each type', function (string $type, string $expectedClass) {
    $event = EventFactory::make(['type' => $type]);

    expect($event)->toBeInstanceOf($expectedClass);
})->with([
    ['ChannelCreated', ChannelCreated::class],
    ['ChannelDestroyed', ChannelDestroyed::class],
    ['ChannelStateChange', ChannelStateChange::class],
    ['BridgeCreated', BridgeCreated::class],
    ['BridgeDestroyed', BridgeDestroyed::class],
    ['DeviceStateChanged', DeviceStateChanged::class],
    ['StasisStart', StasisStart::class],
    ['StasisEnd', StasisEnd::class],
    ['ChannelEnteredBridge', ChannelEnteredBridge::class],
    ['ChannelLeftBridge', ChannelLeftBridge::class],
]);

it('returns null for unknown event types', function () {
    $event = EventFactory::make(['type' => 'UnknownEvent']);

    expect($event)->toBeNull();
});

it('returns null when type is missing', function () {
    $event = EventFactory::make([]);

    expect($event)->toBeNull();
});

it('passes the full payload to the event', function () {
    $payload = [
        'type' => 'StasisStart',
        'timestamp' => '2025-01-01T00:00:00.000+0000',
        'application' => 'test-app',
        'channel' => ['id' => 'ch-1', 'state' => 'Ring'],
        'args' => ['arg1', 'arg2'],
    ];

    $event = EventFactory::make($payload);

    expect($event->payload)->toBe($payload);
});

it('allows registering custom event types', function () {
    EventFactory::register('CustomEvent', CustomTestEvent::class);

    $event = EventFactory::make(['type' => 'CustomEvent', 'data' => 'test']);

    expect($event)->toBeInstanceOf(CustomTestEvent::class)
        ->and($event->payload)->toHaveKey('data', 'test');
});

it('rejects registering a class that does not extend AriEvent', function () {
    EventFactory::register('Bad', stdClass::class);
})->throws(InvalidArgumentException::class);

class CustomTestEvent extends AriEvent {}
