<?php

namespace VoIPforAll\AsteriskAri;

use Illuminate\Support\ServiceProvider;
use VoIPforAll\AsteriskAri\Client\AriClient;
use VoIPforAll\AsteriskAri\Client\AsyncAriClient;
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
                timeout: $config['timeout'],
            );
        });

        $this->app->singleton(AriManager::class, function ($app) {
            return new AriManager($app->make(AriClientInterface::class));
        });

        $this->app->singleton(AsyncAriClient::class, function ($app) {
            $config = $app['config']['asterisk'];

            return new AsyncAriClient(
                host: (string) $config['host'],
                port: (int) $config['port'],
                user: (string) $config['user'],
                password: (string) $config['password'],
                scheme: (string) $config['scheme'],
                timeout: (float) $config['timeout'],
            );
        });

        $this->app->singleton(WebSocketClient::class, function ($app) {
            return WebSocketClient::fromConfig($app['config']['asterisk']);
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
