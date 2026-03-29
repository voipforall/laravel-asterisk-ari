# Laravel Asterisk ARI

A opinionated Laravel package for managing Asterisk PBX through the [Asterisk REST Interface (ARI)](https://docs.asterisk.org/Configuration/Interfaces/Asterisk-REST-Interface-ARI/).

## Requirements

- PHP ^8.2
- Laravel 11, 12 or 13

## Installation

```bash
composer require voipforall/laravel-asterisk-ari
```

Publish the configuration file:

```bash
php artisan vendor:publish --tag=asterisk-config
```

## Configuration

Add the following variables to your `.env` file:

```env
ASTERISK_ARI_HOST=127.0.0.1
ASTERISK_ARI_PORT=8088
ASTERISK_ARI_USER=asterisk
ASTERISK_ARI_PASSWORD=your-secret
ASTERISK_ARI_APP=laravel
ASTERISK_ARI_SCHEME=http
ASTERISK_ARI_WS_SCHEME=ws
ASTERISK_ARI_TIMEOUT=10
```

| Variable | Description | Default |
|---|---|---|
| `ASTERISK_ARI_HOST` | Asterisk server IP or hostname | `127.0.0.1` |
| `ASTERISK_ARI_PORT` | ARI HTTP port | `8088` |
| `ASTERISK_ARI_USER` | ARI username | `asterisk` |
| `ASTERISK_ARI_PASSWORD` | ARI password | _(empty)_ |
| `ASTERISK_ARI_APP` | Stasis application name | `laravel` |
| `ASTERISK_ARI_SCHEME` | HTTP scheme (`http` or `https`) | `http` |
| `ASTERISK_ARI_WS_SCHEME` | WebSocket scheme (`ws` or `wss`) | `ws` |
| `ASTERISK_ARI_TIMEOUT` | HTTP request timeout in seconds | `10` |

## Usage

You can use the `Ari` facade or inject `AriManager` directly:

```php
use VoIPforAll\AsteriskAri\Facades\Ari;

// Via facade
$channels = Ari::channels()->list();

// Via injection
use VoIPforAll\AsteriskAri\AriManager;

public function __construct(private AriManager $ari) {}

public function index()
{
    return $this->ari->channels()->list();
}
```

---

## API Reference

### Channels

Manage active channels (calls) on Asterisk.

```php
use VoIPforAll\AsteriskAri\Facades\Ari;
```

#### List all channels

```php
Ari::channels()->list();
```

#### Get channel details

```php
Ari::channels()->get(string $channelId);
```

#### Originate a call

```php
Ari::channels()->originate(
    endpoint: 'PJSIP/1000',
    extension: '1001',
    context: 'default',
    callerId: '"John" <1000>',
    timeout: 30,
    app: 'laravel',
);
```

| Parameter | Type | Required | Description |
|---|---|---|---|
| `endpoint` | `string` | Yes | Target endpoint (e.g. `PJSIP/1000`) |
| `extension` | `?string` | No | Extension to dial |
| `context` | `?string` | No | Dialplan context |
| `callerId` | `?string` | No | Caller ID string |
| `timeout` | `?int` | No | Dial timeout in seconds |
| `app` | `?string` | No | Stasis application to place channel into |

#### Hangup a channel

```php
Ari::channels()->hangup(string $channelId, string $reason = 'normal');
```

#### Answer a channel

```php
Ari::channels()->answer(string $channelId);
```

#### Hold / Unhold

```php
Ari::channels()->hold(string $channelId);
Ari::channels()->unhold(string $channelId);
```

#### Mute / Unmute

```php
Ari::channels()->mute(string $channelId, string $direction = 'both');
Ari::channels()->unmute(string $channelId, string $direction = 'both');
```

The `$direction` parameter accepts: `both`, `in`, or `out`.

#### Channel Variables

```php
Ari::channels()->getVariable(string $channelId, string $variable);
Ari::channels()->setVariable(string $channelId, string $variable, string $value);
```

#### Send DTMF

```php
Ari::channels()->sendDtmf(string $channelId, string $dtmf);
```

#### Record a channel

```php
Ari::channels()->record(string $channelId, string $name, string $format = 'wav');
```

---

### Bridges

Manage bridges (conference rooms, call mixing).

#### List all bridges

```php
Ari::bridges()->list();
```

#### Get bridge details

```php
Ari::bridges()->get(string $bridgeId);
```

#### Create a bridge

```php
Ari::bridges()->create(string $type = 'mixing', ?string $name = null);
```

Bridge types: `mixing`, `holding`, `dtmf_events`, `proxy_media`.

#### Destroy a bridge

```php
Ari::bridges()->destroy(string $bridgeId);
```

#### Add channels to a bridge

```php
// Single channel
Ari::bridges()->addChannel(bridgeId: '1234', channels: 'channel-id');

// Multiple channels
Ari::bridges()->addChannel(bridgeId: '1234', channels: ['channel-1', 'channel-2']);
```

#### Remove channels from a bridge

```php
Ari::bridges()->removeChannel(bridgeId: '1234', channels: 'channel-id');
Ari::bridges()->removeChannel(bridgeId: '1234', channels: ['channel-1', 'channel-2']);
```

---

### Endpoints

Query registered endpoints (SIP/PJSIP peers).

#### List all endpoints

```php
Ari::endpoints()->list();
```

#### List by technology

```php
Ari::endpoints()->listByTechnology(string $technology);

// Example
Ari::endpoints()->listByTechnology('PJSIP');
```

#### Get a specific endpoint

```php
Ari::endpoints()->get(string $technology, string $resource);

// Example
Ari::endpoints()->get('PJSIP', '1000');
```

---

### Device States

Manage custom device states.

#### List all device states

```php
Ari::deviceStates()->list();
```

#### Get device state

```php
Ari::deviceStates()->get(string $deviceName);
```

#### Update device state

```php
Ari::deviceStates()->update(string $deviceName, string $state);
```

Valid states: `NOT_INUSE`, `INUSE`, `BUSY`, `INVALID`, `UNAVAILABLE`, `RINGING`, `RINGINUSE`, `ONHOLD`.

#### Delete device state

```php
Ari::deviceStates()->delete(string $deviceName);
```

---

### Recordings

Manage stored and live recordings.

#### Stored Recordings

```php
Ari::recordings()->listStored();
Ari::recordings()->getStored(string $name);
Ari::recordings()->deleteStored(string $name);
```

#### Live Recordings

```php
Ari::recordings()->getLive(string $name);
Ari::recordings()->stop(string $name);
Ari::recordings()->pause(string $name);
Ari::recordings()->unpause(string $name);
```

---

### Asterisk

Query Asterisk system information.

#### Get system info

```php
// All info
Ari::asterisk()->info();

// Filtered (build, system, config, status)
Ari::asterisk()->info(only: 'system');
```

#### Ping

```php
Ari::asterisk()->ping(); // Returns true if Asterisk is reachable
```

#### Get global variable

```php
Ari::asterisk()->getVariable(string $variable);
```

---

## WebSocket Events

Listen to real-time ARI events using the built-in Artisan command:

```bash
php artisan ari:listen
```

The command connects to Asterisk via WebSocket with automatic reconnection (exponential backoff up to 60s). Use `SIGTERM` or `SIGINT` (Ctrl+C) for graceful shutdown.

### Supported Events

All events are dispatched as standard Laravel events. Register listeners in your `EventServiceProvider` or use the `Event` facade:

| ARI Event | Laravel Event Class |
|---|---|
| `StasisStart` | `VoIPforAll\AsteriskAri\Events\StasisStart` |
| `StasisEnd` | `VoIPforAll\AsteriskAri\Events\StasisEnd` |
| `ChannelCreated` | `VoIPforAll\AsteriskAri\Events\ChannelCreated` |
| `ChannelDestroyed` | `VoIPforAll\AsteriskAri\Events\ChannelDestroyed` |
| `ChannelStateChange` | `VoIPforAll\AsteriskAri\Events\ChannelStateChange` |
| `ChannelEnteredBridge` | `VoIPforAll\AsteriskAri\Events\ChannelEnteredBridge` |
| `ChannelLeftBridge` | `VoIPforAll\AsteriskAri\Events\ChannelLeftBridge` |
| `BridgeCreated` | `VoIPforAll\AsteriskAri\Events\BridgeCreated` |
| `BridgeDestroyed` | `VoIPforAll\AsteriskAri\Events\BridgeDestroyed` |
| `DeviceStateChanged` | `VoIPforAll\AsteriskAri\Events\DeviceStateChanged` |

### Listening to Events

```php
// In a Listener class
use VoIPforAll\AsteriskAri\Events\StasisStart;

class HandleIncomingCall
{
    public function handle(StasisStart $event): void
    {
        $channel = $event->getChannel();
        $args = $event->getArgs();

        // Handle the incoming call...
    }
}
```

Every event extends `AriEvent` and provides:

```php
$event->payload;          // Full raw payload from ARI
$event->getType();        // Event type string
$event->getTimestamp();   // Event timestamp
$event->getApplication(); // Stasis application name
```

### Registering Custom Events

You can register additional ARI event types:

```php
use VoIPforAll\AsteriskAri\Events\EventFactory;

EventFactory::register('PlaybackFinished', \App\Events\PlaybackFinished::class);
```

The custom event class must extend `VoIPforAll\AsteriskAri\Events\AriEvent`.

## License

MIT
