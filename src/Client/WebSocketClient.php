<?php

namespace VoIPforAll\AsteriskAri\Client;

use Closure;
use Ratchet\Client\Connector;
use Ratchet\Client\WebSocket;
use Ratchet\RFC6455\Messaging\MessageInterface;
use React\EventLoop\Loop;
use React\EventLoop\LoopInterface;

class WebSocketClient
{
    private string $url;

    private ?LoopInterface $loop = null;

    private ?WebSocket $connection = null;

    private bool $shouldReconnect = true;

    private int $reconnectAttempts = 0;

    private const MAX_RECONNECT_DELAY = 60;

    public function __construct(
        private readonly string $host,
        private readonly int $port,
        private readonly string $user,
        private readonly string $password,
        private readonly string $app,
        private readonly string $scheme = 'ws',
    ) {
        $query = http_build_query([
            'api_key' => "{$this->user}:{$this->password}",
            'app' => $this->app,
            'subscribeAll' => 'true',
        ]);

        $this->url = "{$this->scheme}://{$this->host}:{$this->port}/ari/events?{$query}";
    }

    public function listen(Closure $onEvent, ?Closure $onClose = null, ?Closure $onError = null): void
    {
        $this->shouldReconnect = true;
        $this->loop = Loop::get();

        $this->connect($onEvent, $onClose, $onError);

        $this->loop->run();
    }

    private function connect(Closure $onEvent, ?Closure $onClose, ?Closure $onError): void
    {
        $connector = new Connector($this->loop);

        $connector($this->url)->then(
            function (WebSocket $conn) use ($onEvent, $onClose, $onError) {
                $this->connection = $conn;
                $this->reconnectAttempts = 0;

                $conn->on('message', function (MessageInterface $msg) use ($onEvent, $onError) {
                    $data = json_decode((string) $msg, true);

                    if (json_last_error() !== JSON_ERROR_NONE) {
                        $onError?.(new \RuntimeException('Invalid JSON from ARI WebSocket: '.json_last_error_msg()));

                        return;
                    }

                    $onEvent($data);
                });

                $conn->on('close', function ($code = null, $reason = null) use ($onEvent, $onClose, $onError) {
                    $this->connection = null;

                    $onClose?.($code, $reason);

                    $this->scheduleReconnect($onEvent, $onClose, $onError);
                });
            },
            function (\Exception $e) use ($onEvent, $onClose, $onError) {
                $onError?.($e);

                $this->scheduleReconnect($onEvent, $onClose, $onError);
            }
        );
    }

    private function scheduleReconnect(Closure $onEvent, ?Closure $onClose, ?Closure $onError): void
    {
        if (! $this->shouldReconnect || ! $this->loop) {
            $this->loop?->stop();

            return;
        }

        $delay = min(2 ** $this->reconnectAttempts, self::MAX_RECONNECT_DELAY);
        $this->reconnectAttempts++;

        $this->loop->addTimer($delay, fn () => $this->connect($onEvent, $onClose, $onError));
    }

    public function stop(): void
    {
        $this->shouldReconnect = false;
        $this->connection?->close();
        $this->loop?->stop();
    }
}
