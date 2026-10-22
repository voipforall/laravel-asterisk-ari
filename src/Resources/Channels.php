<?php

namespace VoIPforAll\AsteriskAri\Resources;

use InvalidArgumentException;
use VoIPforAll\AsteriskAri\Concerns\BuildsAriPaths;
use VoIPforAll\AsteriskAri\Contracts\AriClientInterface;

class Channels
{
    use BuildsAriPaths;

    private const SPY_DIRECTIONS = ['none', 'both', 'out', 'in'];

    public function __construct(
        private readonly AriClientInterface $client,
    ) {}

    public function list(): array
    {
        return $this->client->get('channels');
    }

    public function get(string $channelId): array
    {
        return $this->client->get("channels/{$this->segment($channelId)}");
    }

    public function originate(
        string $endpoint,
        ?string $extension = null,
        ?string $context = null,
        ?string $callerId = null,
        ?int $timeout = null,
        ?string $app = null,
        ?string $appArgs = null,
        ?string $channelId = null,
        array $variables = [],
        ?int $priority = null,
        ?string $label = null,
        ?string $otherChannelId = null,
    ): array {
        return $this->client->post('channels', $this->withoutNulls([
            'endpoint' => $endpoint,
            'extension' => $extension,
            'context' => $context,
            'priority' => $priority,
            'label' => $label,
            'callerId' => $callerId,
            'timeout' => $timeout,
            'app' => $app,
            'appArgs' => $appArgs,
            'channelId' => $channelId,
            'otherChannelId' => $otherChannelId,
            'variables' => $variables ?: null,
        ]));
    }

    /**
     * Hang up a channel. A textual reason (normal, busy, congestion, no_answer, ...) is sent as "reason";
     * a numeric Q.850 cause code is sent as "reason_code".
     */
    public function hangup(string $channelId, string $reason = 'normal'): array
    {
        return $this->client->delete("channels/{$this->segment($channelId)}", [
            (ctype_digit($reason) ? 'reason_code' : 'reason') => $reason,
        ]);
    }

    public function answer(string $channelId): array
    {
        return $this->client->post("channels/{$this->segment($channelId)}/answer");
    }

    public function hold(string $channelId): array
    {
        return $this->client->post("channels/{$this->segment($channelId)}/hold");
    }

    public function unhold(string $channelId): array
    {
        return $this->client->delete("channels/{$this->segment($channelId)}/hold");
    }

    public function mute(string $channelId, string $direction = 'both'): array
    {
        return $this->client->post("channels/{$this->segment($channelId)}/mute", [
            'direction' => $direction,
        ]);
    }

    public function unmute(string $channelId, string $direction = 'both'): array
    {
        return $this->client->delete("channels/{$this->segment($channelId)}/mute", [
            'direction' => $direction,
        ]);
    }

    public function getVariable(string $channelId, string $variable): array
    {
        return $this->client->get("channels/{$this->segment($channelId)}/variable", [
            'variable' => $variable,
        ]);
    }

    public function setVariable(string $channelId, string $variable, string $value): array
    {
        return $this->client->post("channels/{$this->segment($channelId)}/variable", [
            'variable' => $variable,
            'value' => $value,
        ]);
    }

    public function sendDtmf(string $channelId, string $dtmf): array
    {
        return $this->client->post("channels/{$this->segment($channelId)}/dtmf", [
            'dtmf' => $dtmf,
        ]);
    }

    /**
     * Start recording a channel. Optional arguments are only sent when given, so ARI defaults apply otherwise.
     *
     * @param  string|null  $ifExists  fail, overwrite or append
     * @param  string|null  $terminateOn  none, any, *, #
     */
    public function record(
        string $channelId,
        string $name,
        string $format = 'wav',
        ?int $maxDurationSeconds = null,
        ?int $maxSilenceSeconds = null,
        ?string $ifExists = null,
        ?bool $beep = null,
        ?string $terminateOn = null,
    ): array {
        $this->recordingPath($name);

        return $this->client->post("channels/{$this->segment($channelId)}/record", $this->withoutNulls([
            'name' => $name,
            'format' => $format,
            'maxDurationSeconds' => $maxDurationSeconds,
            'maxSilenceSeconds' => $maxSilenceSeconds,
            'ifExists' => $ifExists,
            'beep' => $beep,
            'terminateOn' => $terminateOn,
        ]));
    }

    /**
     * Play one or more media URIs (sound:, recording:, number:, digits:, characters:, tone:) on a channel.
     * Several media are sent as a JSON array, which ARI plays in sequence.
     *
     * @param  string|array<int, string>  $media
     */
    public function play(
        string $channelId,
        string|array $media,
        ?string $playbackId = null,
        ?string $lang = null,
        ?int $offsetms = null,
        ?int $skipms = null,
    ): array {
        $uri = "channels/{$this->segment($channelId)}/play";

        if ($playbackId !== null) {
            $uri .= "/{$this->segment($playbackId)}";
        }

        return $this->client->post($uri, $this->withoutNulls([
            'media' => is_array($media) ? array_values($media) : $media,
            'lang' => $lang,
            'offsetms' => $offsetms,
            'skipms' => $skipms,
        ]));
    }

    /**
     * Exit Stasis and continue the channel in the dialplan (optionally at a new location).
     */
    public function continueInDialplan(
        string $channelId,
        ?string $context = null,
        ?string $extension = null,
        ?int $priority = null,
        ?string $label = null,
    ): array {
        return $this->client->post("channels/{$this->segment($channelId)}/continue", $this->withoutNulls([
            'context' => $context,
            'extension' => $extension,
            'priority' => $priority,
            'label' => $label,
        ]));
    }

    /**
     * Redirect the channel to a different endpoint (e.g. PJSIP/1000).
     */
    public function redirect(string $channelId, string $endpoint): array
    {
        return $this->client->post("channels/{$this->segment($channelId)}/redirect", [
            'endpoint' => $endpoint,
        ]);
    }

    /**
     * Create a snoop channel on another channel and place it in a Stasis application.
     * Whisper defaults to "none" so a snoop never injects audio unless explicitly asked to.
     *
     * @param  string  $spy  none, both, out or in
     * @param  string  $whisper  none, both, out or in
     *
     * @throws InvalidArgumentException when spy or whisper are not valid directions.
     */
    public function snoop(
        string $channelId,
        string $app,
        string $spy = 'both',
        string $whisper = 'none',
        ?string $appArgs = null,
        ?string $snoopId = null,
    ): array {
        foreach (['spy' => $spy, 'whisper' => $whisper] as $option => $direction) {
            if (! in_array($direction, self::SPY_DIRECTIONS, true)) {
                throw new InvalidArgumentException("Invalid snoop {$option} direction: {$direction}.");
            }
        }

        $uri = "channels/{$this->segment($channelId)}/snoop";

        if ($snoopId !== null) {
            $uri .= "/{$this->segment($snoopId)}";
        }

        return $this->client->post($uri, $this->withoutNulls([
            'app' => $app,
            'spy' => $spy,
            'whisper' => $whisper,
            'appArgs' => $appArgs,
        ]));
    }

    /**
     * Create an External Media channel that streams the audio to/from an external host (RTP, AudioSocket or WebSocket).
     *
     * @param  string|null  $externalHost  host:port, required unless connectionType is "server" (WebSocket)
     * @param  array<string, string>  $variables
     */
    public function externalMedia(
        string $app,
        string $format,
        ?string $externalHost = null,
        ?string $channelId = null,
        string $encapsulation = 'rtp',
        string $transport = 'udp',
        ?string $connectionType = null,
        ?string $direction = null,
        array $variables = [],
        ?string $data = null,
        ?string $transportData = null,
    ): array {
        return $this->client->post('channels/externalMedia', $this->withoutNulls([
            'app' => $app,
            'format' => $format,
            'external_host' => $externalHost,
            'channelId' => $channelId,
            'encapsulation' => $encapsulation,
            'transport' => $transport,
            'connection_type' => $connectionType,
            'direction' => $direction,
            'variables' => $variables ?: null,
            'data' => $data,
            'transport_data' => $transportData,
        ]));
    }
}
