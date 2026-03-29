<?php

namespace VoIPforAll\AsteriskAri\Commands;

use Illuminate\Console\Command;
use VoIPforAll\AsteriskAri\Client\WebSocketClient;
use VoIPforAll\AsteriskAri\Events\EventFactory;

class AriListenCommand extends Command
{
    protected $signature = 'ari:listen';

    protected $description = 'Listen to Asterisk ARI events via WebSocket';

    public function handle(WebSocketClient $ws): int
    {
        $this->info('Connecting to Asterisk ARI WebSocket...');

        $this->trap([SIGTERM, SIGINT], function () use ($ws) {
            $this->warn('Shutting down...');
            $ws->stop();
        });

        $ws->listen(
            onEvent: function (array $data) {
                $type = $data['type'] ?? 'Unknown';

                $this->line("[{$type}] ".json_encode($data, JSON_UNESCAPED_SLASHES));

                $event = EventFactory::make($data);

                if ($event) {
                    event($event);
                }
            },
            onClose: function ($code, $reason) {
                $this->warn("Connection closed: [{$code}] {$reason}");
            },
            onError: function (\Exception $e) {
                $this->error("Connection error: {$e->getMessage()}");
            },
        );

        return self::SUCCESS;
    }
}
