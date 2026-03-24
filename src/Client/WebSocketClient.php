<?php

namespace VoIPforAll\AsteriskAri\Client;

use Closure;
use Ratchet\Client\Connector;
use Ratchet\Client\WebSocket;
use Ratchet\RFC6455\Messaging\MessageInterface;
use React\EventLoop\Loop;
use React\EventLoop\LoopInterface;
use VoIPforAll\AsteriskAri\Exceptions\AriConnectionException;

class WebSocketClient
{
    private string $url;

    private ?LoopInterface $loop = null;

    public function __construct(
        private readonly string $host,
        private readonly int $port,
        private readonly string $user,
        private readonly string $password,
        private readonly string $app,
        private readonly string $scheme = 'ws',
    ) {
        $this->url = sprintf(
            '%s://%s:%d/ari/events?api_key=%s:%s&app=%s&subscribeAll=true',
            $this->scheme,
            $this->host,
            $this->port,
            $this->user,
            $this->password,
            $this->app,
        );
    }

    public function listen(Closure $onEvent, ?Closure $onError = null): void
    {
        $this->loop = Loop::get();
        $connector = new Connector($this->loop);

        $connector($this->url)->then(
            function (WebSocket $conn) use ($onEvent, $onError) {
                $conn->on('message', function (MessageInterface $msg) use ($onEvent) {
                    $data = json_decode((string) $msg, true);

                    if (json_last_error() === JSON_ERROR_NONE) {
                        $onEvent($data);
                    }
                });

                $conn->on('close', function ($code = null, $reason = null) {
                    throw new AriConnectionException("WebSocket closed: [{$code}] {$reason}");
                });
            },
            function (\Exception $e) use ($onError) {
                if ($onError) {
                    $onError($e);
                } else {
                    throw new AriConnectionException("WebSocket connection failed: {$e->getMessage()}", previous: $e);
                }
            }
        );

        $this->loop->run();
    }

    public function stop(): void
    {
        $this->loop?->stop();
    }
}
