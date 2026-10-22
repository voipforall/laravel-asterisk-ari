# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.1.1] - 2026-10-22

### Fixed
- `getRaw()` moved to the new `RawAriClientInterface` (extends `AriClientInterface`), so custom implementations of `AriClientInterface` written for 1.0 keep working; `Recordings::getStoredFile()` requires a raw-capable client.

## [1.1.0] - 2026-10-22

### Added
- `AriClientInterface::getRaw()` (raw/binary GET) implemented by `AriClient`
- Recordings: `getStoredFile()` (download audio as binary) and `copyStored()`, with recording-name validation against path traversal
- Channels: `play()`, `continueInDialplan()`, `redirect()`, `snoop()` (whisper `none` by default), `externalMedia()` (RTP, AudioSocket, WebSocket client/server)
- Channels: `record()` accepts `maxDurationSeconds`, `maxSilenceSeconds`, `ifExists`, `beep` and `terminateOn`
- Channels: `originate()` accepts `appArgs`, `channelId`, `variables`, `priority`, `label` and `otherChannelId`
- New `Playbacks` resource (`get`, `stop`, `control`) and `Applications` resource (`list`, `get`, `subscribe`, `unsubscribe`), exposed by `AriManager` and the `Ari` facade
- Asterisk: `setVariable()`, `listModules()`, `getModule()`, `loadModule()`, `unloadModule()`, `reloadModule()`
- `WebSocketClient`: configurable `subscribeAll` (constructor and `asterisk.subscribe_all` / `ASTERISK_ARI_SUBSCRIBE_ALL`, default `true`), several applications (array or comma-separated) and `WebSocketClient::fromConfig()`
- `AsyncAriClient`: non-blocking ARI client on the ReactPHP event loop returning promises, with timeout and cancellation
- Integration test suite (`--group=integration`, enabled with `ARI_INTEGRATION=1`)

### Fixed
- `AriClient::get()` dropped query parameters (e.g. `Asterisk::info(only: ...)`, `getVariable()` and `Channels::getVariable()` failed with "Variable name is required")
- `Channels::hangup()` sent textual reasons as `reason_code`, which Asterisk rejects; textual reasons are now sent as `reason` and numeric causes as `reason_code`

## [1.0.1] - 2026-03-29

### Added
- CONTRIBUTING.md with development guidelines
- SECURITY.md with vulnerability reporting policy

### Fixed
- Author email in package metadata

## [1.0.0] - 2026-03-29

### Added
- ARI HTTP client with Basic Auth and configurable timeout
- WebSocket client with automatic reconnection (exponential backoff)
- Facade `Ari` for fluent API access
- Resource classes: Channels, Bridges, Endpoints, DeviceStates, Recordings, Asterisk
- Channel operations: originate, hangup, answer, hold/unhold, mute/unmute, DTMF, record, variables
- Bridge operations: create, destroy, add/remove channels
- Endpoint queries: list, get, filter by technology
- Device state management: list, get, update, delete
- Recording management: stored (list, get, delete) and live (get, stop, pause/unpause)
- Asterisk system info, ping, and global variables
- Real-time event system via WebSocket with Laravel event dispatching
- Events: StasisStart, StasisEnd, ChannelCreated, ChannelDestroyed, ChannelStateChange, ChannelEnteredBridge, ChannelLeftBridge, BridgeCreated, BridgeDestroyed, DeviceStateChanged
- Custom event registration via `EventFactory::register()`
- Artisan command `ari:listen` with graceful shutdown (SIGTERM/SIGINT)
- Full test suite (99 tests) with Pest 4
- Support for Laravel 11, 12, and 13
