<?php

use VoIPforAll\AsteriskAri\Events\StasisStart;

it('returns the event type', function () {
    $event = new StasisStart(['type' => 'StasisStart']);

    expect($event->getType())->toBe('StasisStart');
});

it('returns Unknown when type is missing', function () {
    $event = new StasisStart([]);

    expect($event->getType())->toBe('Unknown');
});

it('returns the timestamp', function () {
    $event = new StasisStart([
        'type' => 'StasisStart',
        'timestamp' => '2025-01-01T00:00:00.000+0000',
    ]);

    expect($event->getTimestamp())->toBe('2025-01-01T00:00:00.000+0000');
});

it('returns null when timestamp is missing', function () {
    $event = new StasisStart(['type' => 'StasisStart']);

    expect($event->getTimestamp())->toBeNull();
});

it('returns the application name', function () {
    $event = new StasisStart([
        'type' => 'StasisStart',
        'application' => 'my-app',
    ]);

    expect($event->getApplication())->toBe('my-app');
});

it('returns null when application is missing', function () {
    $event = new StasisStart(['type' => 'StasisStart']);

    expect($event->getApplication())->toBeNull();
});

it('exposes the full payload', function () {
    $payload = [
        'type' => 'StasisStart',
        'channel' => ['id' => 'ch-1'],
        'args' => ['hello'],
    ];

    $event = new StasisStart($payload);

    expect($event->payload)->toBe($payload);
});
