<p align="center">
<img src="https://github.com/voipforall/tftp-client/assets/30990097/cd0f5703-190d-4ab3-bf7d-21bce29fbf67">
</p>

# Laravel Asterisk ARI

[![Latest Version on Packagist](https://img.shields.io/packagist/v/voipforall/laravel-asterisk-ari.svg?style=flat-square)](https://packagist.org/packages/voipforall/laravel-asterisk-ari)
[![Total Downloads](https://img.shields.io/packagist/dt/voipforall/laravel-asterisk-ari.svg?style=flat-square)](https://packagist.org/packages/voipforall/laravel-asterisk-ari)
![GitHub Actions](https://github.com/voipforall/laravel-asterisk-ari/actions/workflows/tests.yml/badge.svg)

An opinionated Laravel package for managing Asterisk PBX through the [Asterisk REST Interface (ARI)](https://docs.asterisk.org/Configuration/Interfaces/Asterisk-REST-Interface-ARI/).

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
ASTERISK_ARI_SUBSCRIBE_ALL=true
```

| Variable | Description | Default |
|---|---|---|
| `ASTERISK_ARI_HOST` | Asterisk server IP or hostname | `127.0.0.1` |
| `ASTERISK_ARI_PORT` | ARI HTTP port | `8088` |
| `ASTERISK_ARI_USER` | ARI username | `asterisk` |
| `ASTERISK_ARI_PASSWORD` | ARI password | _(required)_ |
| `ASTERISK_ARI_APP` | Stasis application name | `laravel` |
| `ASTERISK_ARI_SCHEME` | HTTP scheme (`http` or `https`) | `http` |
| `ASTERISK_ARI_WS_SCHEME` | WebSocket scheme (`ws` or `wss`) | `ws` |
| `ASTERISK_ARI_TIMEOUT` | HTTP request timeout in seconds | `10` |
| `ASTERISK_ARI_SUBSCRIBE_ALL` | WebSocket receives every system event (`true`) or only the app's own channels and subscriptions (`false`) | `true` |

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
| `appArgs` | `?string` | No | Arguments passed to the Stasis application (comma-separated) |
| `channelId` | `?string` | No | Unique id to assign to the new channel |
| `variables` | `array` | No | Channel variables to set (`['NAME' => 'value']`) |
| `priority` / `label` | `?int` / `?string` | No | Dialplan priority or label to start at |
| `otherChannelId` | `?string` | No | Unique id for the second leg of a `Local` channel |

#### Hangup a channel

```php
Ari::channels()->hangup(string $channelId, string $reason = 'normal');
```

A textual reason (`normal`, `busy`, `congestion`, `no_answer`, ...) is sent as ARI's `reason`; a numeric Q.850 cause (e.g. `'17'`) is sent as `reason_code`.

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

#### Record with options

All options are optional and only sent when given (ARI defaults apply otherwise):

```php
Ari::channels()->record(
    channelId: $channelId,
    name: 'voicebot/turn-1',
    format: 'wav',
    maxDurationSeconds: 30,
    maxSilenceSeconds: 2,
    ifExists: 'overwrite', // fail | overwrite | append
    beep: false,
    terminateOn: 'none',   // none | any | * | #
);
```

#### Play media

```php
// A single media URI (sound:, recording:, number:, digits:, characters:, tone:)
Ari::channels()->play($channelId, 'sound:hello-world');

// Several media in sequence, with your own playback id and language
Ari::channels()->play($channelId, ['sound:hello-world', 'recording:voicebot/answer-1'], playbackId: 'pb-1', lang: 'pt_BR');
```

#### Continue in the dialplan / redirect

```php
Ari::channels()->continueInDialplan($channelId);                         // next priority
Ari::channels()->continueInDialplan($channelId, 'internal', '200', 1);    // new location
Ari::channels()->continueInDialplan($channelId, label: 'menu');
Ari::channels()->redirect($channelId, 'PJSIP/1000');
```

#### Snoop

Creates a snoop channel and places it in a Stasis application. `whisper` defaults to `none`, so a snoop never injects audio unless you ask for it.

```php
Ari::channels()->snoop($channelId, app: 'sentiment', spy: 'in', whisper: 'none', appArgs: 'call,42', snoopId: 'snoop-42');
```

#### External media

Streams a channel's audio to/from an external process (RTP, AudioSocket or Asterisk's WebSocket media channel):

```php
// RTP to a local media server
Ari::channels()->externalMedia(app: 'voicebot', format: 'slin16', externalHost: '127.0.0.1:40000');

// Asterisk acts as a WebSocket server (Asterisk 20.15+/22.5+)
Ari::channels()->externalMedia(
    app: 'voicebot',
    format: 'ulaw',
    encapsulation: 'none',
    transport: 'websocket',
    connectionType: 'server',
);
```

---

### Playbacks

```php
Ari::playbacks()->get(string $playbackId);
Ari::playbacks()->stop(string $playbackId);
Ari::playbacks()->control(string $playbackId, 'pause'); // restart | pause | unpause | reverse | forward
```

---

### Applications

```php
Ari::applications()->list();
Ari::applications()->get('voicebot');
Ari::applications()->subscribe('voicebot', ['channel:'.$channelId, 'bridge:'.$bridgeId]);
Ari::applications()->unsubscribe('voicebot', 'channel:'.$channelId);
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

// Download the audio as a binary string (works with a remote Asterisk, no shared disk needed)
$wav = Ari::recordings()->getStoredFile('voicebot/turn-1');

Ari::recordings()->copyStored('voicebot/turn-1', 'archive/turn-1');
```

Recording names may contain sub-directories; absolute names and `.`/`..` segments are rejected with `InvalidArgumentException`.

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
Ari::asterisk()->setVariable(string $variable, string $value);
```

#### Modules

```php
Ari::asterisk()->listModules();
Ari::asterisk()->getModule('res_pjsip.so');
Ari::asterisk()->loadModule('app_queue.so');
Ari::asterisk()->unloadModule('app_queue.so');
Ari::asterisk()->reloadModule('res_pjsip.so');
```

---

### Raw binary requests

`RawAriClientInterface::getRaw(string $uri, array $query = []): string` (implemented by the bundled `AriClient`) returns the raw response body used by `getStoredFile()`, with the same `AriException` / `AriConnectionException` errors as the JSON methods. Custom implementations of `AriClientInterface` keep working; implement `RawAriClientInterface` to enable recording downloads.

---

### Async client (ReactPHP)

`AsyncAriClient` calls ARI without blocking the ReactPHP event loop used by the WebSocket client, so a long-running daemon can react to events and call ARI at the same time. It has the same generic methods, returning `React\Promise\PromiseInterface`:

```php
use VoIPforAll\AsteriskAri\Client\AsyncAriClient;

$ari = app(AsyncAriClient::class);

$ari->post("channels/{$channelId}/play", ['media' => 'sound:beep'])
    ->then(fn (array $playback) => logger()->info('playing', ['id' => $playback['id']]))
    ->catch(fn (\Throwable $e) => report($e));

$ari->getRaw("recordings/stored/{$name}/file")->then(fn (string $wav) => /* ... */ null);
```

Every request is bounded by `ASTERISK_ARI_TIMEOUT` (rejects with `AriConnectionException('ARI request timed out')`), can be cancelled with `$promise->cancel()`, and HTTP errors reject with `AriException` (message from ARI, HTTP status as code).

---

## WebSocket Events

Listen to real-time ARI events using the built-in Artisan command:

```bash
php artisan ari:listen
```

The command connects to Asterisk via WebSocket with automatic reconnection (exponential backoff up to 60s). Use `SIGTERM` or `SIGINT` (Ctrl+C) for graceful shutdown.

### Choosing applications and subscribeAll

By default the WebSocket subscribes to every event in the system (`subscribeAll=true`, the v1.0 behaviour). A dedicated daemon can listen to one or more applications and only receive the events of its own channels and explicit subscriptions:

```php
use VoIPforAll\AsteriskAri\Client\WebSocketClient;

$ws = WebSocketClient::fromConfig(config('asterisk'), app: ['voicebot', 'sentiment'], subscribeAll: false);

$ws->listen(onEvent: fn (array $event) => /* ... */ null);
```

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

### Testing

```bash
composer test
```

Integration tests run against a real Asterisk and are skipped unless `ARI_INTEGRATION=1`. Credentials come only from the environment:

```bash
ARI_INTEGRATION=1 ARI_HOST=127.0.0.1 ARI_PORT=8088 ARI_USER=... ARI_PASSWORD=... \
    vendor/bin/pest --group=integration
```

They register the Stasis app `vmtest-ari`, originate `Local/*43@internal` (override with `ARI_TEST_ENDPOINT`; the target must answer and keep the call up) and continue it to `internal,*43,1` (`ARI_TEST_CONTINUE`). Everything they create uses the `vmtest-` prefix and is removed afterwards.

### Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information what has changed recently.

### Security

If you discover any security related issues, please email cristiano@voipforall.com.br instead of using the issue tracker.

## Credits

-   [VoIPforAll](https://github.com/voipforall)
-   [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE) for more information.
