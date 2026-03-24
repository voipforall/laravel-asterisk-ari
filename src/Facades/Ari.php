<?php

namespace VoIPforAll\AsteriskAri\Facades;

use Illuminate\Support\Facades\Facade;
use VoIPforAll\AsteriskAri\AriManager;

/**
 * @method static \VoIPforAll\AsteriskAri\Resources\Endpoints endpoints()
 * @method static \VoIPforAll\AsteriskAri\Resources\Channels channels()
 * @method static \VoIPforAll\AsteriskAri\Resources\Bridges bridges()
 * @method static \VoIPforAll\AsteriskAri\Resources\DeviceStates deviceStates()
 * @method static \VoIPforAll\AsteriskAri\Resources\Recordings recordings()
 * @method static \VoIPforAll\AsteriskAri\Resources\Asterisk asterisk()
 *
 * @see \VoIPforAll\AsteriskAri\AriManager
 */
class Ari extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return AriManager::class;
    }
}
