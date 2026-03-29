# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

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
