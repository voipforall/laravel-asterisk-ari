<?php

namespace VoIPforAll\AsteriskAri\Client;

use Closure;
use Exception;
use Ratchet\Client\Connector;
use Ratchet\Client\WebSocket;
use Ratchet\RFC6455\Messaging\MessageInterface;
use React\EventLoop\Loop;
use React\EventLoop\LoopInterface;
use RuntimeException;

class WebSocketClient
{
    private ?LoopInterface $loop = null;

    private ?WebSocket $connection = null;

    private bool $shouldReconnect = true;

    private int $reconnectAttempts = 0;

    private const MAX_RECONNECT_DELAY = 60;

    private readonly string $app;

    /**
     * @param  string|array<int, string>  $app  one Stasis application, or several (array or comma-separated)
     * @param  bool  $subscribeAll  receive every event in the system (true, v1.0 behaviour) or only the
     *                              application's own channels/subscriptions (false)
     */
    public function __construct(
        private readonly string $host,
        private readonly int $port,
        private readonly string $user,
        private readonly string $password,
        string|array $app,
        private readonly string $scheme = 'ws',
        private readonly bool $subscribeAll = true,
    ) {
        $this->app = is_array($app) ? implode(',', $app) : $app;
    }

    /**
     * Build a client from the package configuration, optionally overriding the application(s) and subscribeAll.
     *
     * @param  array<string, mixed>  $config  the "asterisk" configuration array
     * @param  string|array<int, string>|null  $app
     */
    public static function fromConfig(array $config, string|array|null $app = null, ?bool $subscribeAll = null): self
    {
        return new self(
            host: (string) $config['host'],
            port: (int) $config['port'],
            user: (string) $config['user'],
            password: (string) $config['password'],
            app: $app ?? $config['app'],
            scheme: (string) ($config['ws_scheme'] ?? 'ws'),
            subscribeAll: $subscribeAll ?? filter_var($config['subscribe_all'] ?? true, FILTER_VALIDATE_BOOL),
        );
    }

    public function app(): string
    {
        return $this->app;
    }

    public function subscribesAll(): bool
    {
        return $this->subscribeAll;
    }

    private function buildUrl(): string
    {
        $query = http_build_query([
            'api_key' => "{$this->user}:{$this->password}",
            'app' => $this->app,
            'subscribeAll' => $this->subscribeAll ? 'true' : 'false',
        ]);

        return "{$this->scheme}://{$this->host}:{$this->port}/ari/events?{$query}";
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

        $connector($this->buildUrl())->then(
            function (WebSocket $conn) use ($onEvent, $onClose, $onError) {
                $this->connection = $conn;
                $this->reconnectAttempts = 0;

                $conn->on('message', function (MessageInterface $msg) use ($onEvent, $onError) {
                    $data = json_decode((string) $msg, true);

                    if (json_last_error() !== JSON_ERROR_NONE) {
                        if ($onError) {
                            $onError(new RuntimeException('Invalid JSON from ARI WebSocket: '.json_last_error_msg()));
                        }

                        return;
                    }

                    $onEvent($data);
                });

                $conn->on('close', function ($code = null, $reason = null) use ($onEvent, $onClose, $onError) {
                    $this->connection = null;

                    if ($onClose) {
                        $onClose($code, $reason);
                    }

                    $this->scheduleReconnect($onEvent, $onClose, $onError);
                });
            },
            function (Exception $e) use ($onEvent, $onClose, $onError) {
                if ($onError) {
                    $onError($this->redact($e));
                }

                $this->scheduleReconnect($onEvent, $onClose, $onError);
            }
        );
    }

    /**
     * Strip the credentials (sent as the api_key query parameter) from connection errors, since some
     * connector errors embed the full WebSocket URL.
     */
    private function redact(Exception $e): Exception
    {
        $secrets = array_filter([
            "{$this->user}:{$this->password}",
            urlencode("{$this->user}:{$this->password}"),
            rawurlencode("{$this->user}:{$this->password}"),
            $this->password,
        ], fn (string $secret) => $secret !== '');

        $message = str_replace($secrets, '***', $e->getMessage());

        return $message === $e->getMessage() ? $e : new RuntimeException($message, (int) $e->getCode());
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
