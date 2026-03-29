<?php

namespace VoIPforAll\AsteriskAri\Tests;

use Orchestra\Testbench\TestCase as Orchestra;
use VoIPforAll\AsteriskAri\AsteriskAriServiceProvider;

class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [
            AsteriskAriServiceProvider::class,
        ];
    }

    protected function getPackageAliases($app): array
    {
        return [
            'Ari' => \VoIPforAll\AsteriskAri\Facades\Ari::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('asterisk.host', '127.0.0.1');
        $app['config']->set('asterisk.port', 8088);
        $app['config']->set('asterisk.user', 'test');
        $app['config']->set('asterisk.password', 'test123');
        $app['config']->set('asterisk.app', 'test-app');
        $app['config']->set('asterisk.scheme', 'http');
        $app['config']->set('asterisk.ws_scheme', 'ws');
        $app['config']->set('asterisk.timeout', 10);
    }
}
