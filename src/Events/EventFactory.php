<?php

namespace VoIPforAll\AsteriskAri\Events;

class EventFactory
{
    private static array $map = [
        'ChannelCreated' => ChannelCreated::class,
        'ChannelDestroyed' => ChannelDestroyed::class,
        'ChannelStateChange' => ChannelStateChange::class,
        'BridgeCreated' => BridgeCreated::class,
        'BridgeDestroyed' => BridgeDestroyed::class,
        'DeviceStateChanged' => DeviceStateChanged::class,
        'StasisStart' => StasisStart::class,
        'StasisEnd' => StasisEnd::class,
        'ChannelEnteredBridge' => ChannelEnteredBridge::class,
        'ChannelLeftBridge' => ChannelLeftBridge::class,
    ];

    public static function make(array $payload): ?AriEvent
    {
        $type = $payload['type'] ?? null;

        if (! $type || ! isset(self::$map[$type])) {
            return null;
        }

        return new self::$map[$type]($payload);
    }

    public static function register(string $type, string $eventClass): void
    {
        if (! is_subclass_of($eventClass, AriEvent::class)) {
            throw new \InvalidArgumentException("{$eventClass} must extend ".AriEvent::class);
        }

        self::$map[$type] = $eventClass;
    }
}
