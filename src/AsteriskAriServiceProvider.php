<?php

namespace VoIPforAll\AsteriskAri;

use Illuminate\Support\ServiceProvider;
use VoIPforAll\AsteriskAri\Client\AriClient;
use VoIPforAll\AsteriskAri\Client\WebSocketClient;
use VoIPforAll\AsteriskAri\Contracts\AriClientInterface;

class AsteriskAriServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/asterisk.php', 'asterisk');

        $this->app->singleton(AriClientInterface::class, function ($app) {
            $config = $app['config']['asterisk'];

            return new AriClient(
                host: $config['host'],
                port: $config['port'],
                user: $config['user'],
                password: $config['password'],
                scheme: $config['scheme'],
            );
        });

        $this->app->singleton(AriManager::class, function ($app) {
            return new AriManager($app->make(AriClientInterface::class));
        });

        $this->app->singleton(WebSocketClient::class, function ($app) {
            $config = $app['config']['asterisk'];

            return new WebSocketClient(
                host: $config['host'],
                port: $config['port'],
                user: $config['user'],
                password: $config['password'],
                app: $config['app'],
                scheme: $config['ws_scheme'],
            );
        });
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/asterisk.php' => config_path('asterisk.php'),
            ], 'asterisk-config');

            $this->commands([
                Commands\AriListenCommand::class,
            ]);
        }
    }
}
