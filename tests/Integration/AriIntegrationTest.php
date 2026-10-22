<?php

/**
 * Integration tests against a real Asterisk (ARI). Skipped unless ARI_INTEGRATION=1.
 *
 * Environment: ARI_HOST, ARI_PORT, ARI_USER, ARI_PASSWORD (never stored in a file), optional
 * ARI_TEST_ENDPOINT (a dialplan target that answers and keeps the call up, default Local/*43@internal)
 * and ARI_TEST_CONTINUE (context,extension,priority used by continueInDialplan, default internal,*43,1).
 * Everything created uses the "vmtest-" prefix and is cleaned up.
 */

use Ratchet\Client\Connector;
use Ratchet\Client\WebSocket;
use Ratchet\RFC6455\Messaging\MessageInterface;
use React\EventLoop\Loop;
use React\Promise\PromiseInterface;
use VoIPforAll\AsteriskAri\AriManager;
use VoIPforAll\AsteriskAri\Client\AriClient;
use VoIPforAll\AsteriskAri\Client\AsyncAriClient;
use VoIPforAll\AsteriskAri\Client\WebSocketClient;
use VoIPforAll\AsteriskAri\Exceptions\AriException;
use VoIPforAll\AsteriskAri\Tests\TestCase;

uses(TestCase::class)->group('integration');

const VMTEST_APP = 'vmtest-ari';

/**
 * Minimal event collector: keeps a WebSocket open for the test Stasis app (registering it in Asterisk)
 * and runs the loop only while waiting for an event.
 */
final class VmtestEvents
{
    /** @var array<int, array<string, mixed>> */
    public array $events = [];

    private ?WebSocket $connection = null;

    public function __construct(private readonly string $url) {}

    public function connect(): void
    {
        $error = null;

        (new Connector(Loop::get()))($this->url)->then(
            function (WebSocket $conn) {
                $this->connection = $conn;
                $conn->on('message', function (MessageInterface $message) {
                    $this->events[] = json_decode((string) $message, true) ?? [];
                    Loop::stop();
                });
                Loop::stop();
            },
            function (Throwable $e) use (&$error) {
                $error = $e;
                Loop::stop();
            },
        );

        $guard = Loop::addTimer(5, fn () => Loop::stop());
        Loop::run();
        Loop::cancelTimer($guard);

        if (! $this->connection) {
            throw new RuntimeException('Could not open the ARI WebSocket: '.($error?->getMessage() ?? 'timeout'));
        }
    }

    /**
     * @param  Closure(array<string, mixed>): bool  $match
     * @return array<string, mixed>
     */
    public function waitFor(Closure $match, float $timeout = 10): array
    {
        $deadline = microtime(true) + $timeout;
        $seen = 0;

        while (true) {
            for (; $seen < count($this->events); $seen++) {
                if ($match($this->events[$seen])) {
                    return array_splice($this->events, $seen, 1)[0];
                }
            }

            $remaining = $deadline - microtime(true);

            if ($remaining <= 0) {
                throw new RuntimeException('Timed out waiting for an ARI event.');
            }

            $guard = Loop::addTimer($remaining, fn () => Loop::stop());
            Loop::run();
            Loop::cancelTimer($guard);
        }
    }

    public function close(): void
    {
        $this->connection?->close();
        $this->connection = null;
        Loop::addTimer(0.05, fn () => Loop::stop());
        Loop::run();
    }
}

function vmtestEventOf(string $type, string $channelId): Closure
{
    return fn (array $event) => ($event['type'] ?? null) === $type && ($event['channel']['id'] ?? null) === $channelId;
}

/**
 * @return mixed
 */
function vmtestAwait(PromiseInterface $promise)
{
    $result = null;
    $error = null;
    $done = false;

    $promise->then(
        function ($value) use (&$result, &$done) {
            [$result, $done] = [$value, true];
            Loop::stop();
        },
        function (Throwable $e) use (&$error, &$done) {
            [$error, $done] = [$e, true];
            Loop::stop();
        },
    );

    if (! $done) {
        $guard = Loop::addTimer(10, fn () => Loop::stop());
        Loop::run();
        Loop::cancelTimer($guard);
    }

    if ($error) {
        throw $error;
    }

    return $result;
}

beforeEach(function () {
    if (getenv('ARI_INTEGRATION') !== '1') {
        $this->markTestSkipped('Set ARI_INTEGRATION=1 (and ARI_HOST/ARI_PORT/ARI_USER/ARI_PASSWORD) to run.');
    }

    $this->config = [
        'host' => getenv('ARI_HOST') ?: '127.0.0.1',
        'port' => (int) (getenv('ARI_PORT') ?: 8088),
        'user' => (string) getenv('ARI_USER'),
        'password' => (string) getenv('ARI_PASSWORD'),
        'app' => VMTEST_APP,
        'ws_scheme' => 'ws',
    ];

    $this->ari = new AriManager(new AriClient(
        $this->config['host'], $this->config['port'], $this->config['user'], $this->config['password'],
    ));

    $ws = WebSocketClient::fromConfig($this->config, VMTEST_APP, false);
    $this->events = new VmtestEvents((new ReflectionClass($ws))->getMethod('buildUrl')->invoke($ws));
    $this->events->connect();

    $this->cleanup = [];
});

afterEach(function () {
    foreach (array_reverse($this->cleanup ?? []) as $cleanup) {
        try {
            $cleanup();
        } catch (Throwable) {
            // Best-effort cleanup of vmtest- resources.
        }
    }

    if (isset($this->events)) {
        $this->events->close();
    }
});

function vmtestOriginate(object $test, string $suffix): string
{
    $channelId = 'vmtest-'.$suffix.'-'.bin2hex(random_bytes(4));

    $test->ari->channels()->originate(
        endpoint: getenv('ARI_TEST_ENDPOINT') ?: 'Local/*43@internal',
        app: VMTEST_APP,
        appArgs: 'integration',
        channelId: $channelId,
        variables: ['VMTEST' => '1'],
    );
    $test->cleanup[] = fn () => $test->ari->channels()->hangup($channelId);

    $start = $test->events->waitFor(vmtestEventOf('StasisStart', $channelId));
    expect($start['args'] ?? [])->toBe(['integration']);

    $test->ari->channels()->answer($channelId);

    return $channelId;
}

it('registers the test application with subscribeAll disabled', function () {
    $apps = collect($this->ari->applications()->list())->pluck('name');

    expect($apps)->toContain(VMTEST_APP);
});

it('plays and controls media on a Stasis channel', function () {
    $channelId = vmtestOriginate($this, 'play');

    $this->ari->channels()->play($channelId, 'sound:beep', 'vmtest-pb-beep');
    $finished = $this->events->waitFor(fn ($e) => ($e['type'] ?? null) === 'PlaybackFinished'
        && ($e['playback']['id'] ?? null) === 'vmtest-pb-beep');
    expect($finished['playback']['media_uri'])->toBe('sound:beep');

    $playbackId = 'vmtest-pb-'.bin2hex(random_bytes(4));
    $this->ari->channels()->play($channelId, ['sound:tt-monkeys', 'sound:tt-monkeysintro'], $playbackId, 'en');
    $this->cleanup[] = fn () => $this->ari->playbacks()->stop($playbackId);
    $this->events->waitFor(fn ($e) => ($e['type'] ?? null) === 'PlaybackStarted' && ($e['playback']['id'] ?? null) === $playbackId);

    expect($this->ari->playbacks()->get($playbackId))->toHaveKey('id', $playbackId);

    $this->ari->playbacks()->control($playbackId, 'pause');
    $this->ari->playbacks()->control($playbackId, 'unpause');
    $this->ari->playbacks()->stop($playbackId);

    $this->events->waitFor(fn ($e) => ($e['type'] ?? null) === 'PlaybackFinished' && ($e['playback']['id'] ?? null) === $playbackId);
});

it('records with options, downloads, copies and deletes the stored file', function () {
    $channelId = vmtestOriginate($this, 'rec');
    $name = 'vmtest-rec-'.bin2hex(random_bytes(4));
    $copy = $name.'-copy';
    $this->cleanup[] = fn () => $this->ari->recordings()->deleteStored($name);
    $this->cleanup[] = fn () => $this->ari->recordings()->deleteStored($copy);

    $this->ari->channels()->record($channelId, $name, 'wav', maxDurationSeconds: 2, maxSilenceSeconds: 1, ifExists: 'overwrite', beep: false, terminateOn: 'none');

    $this->events->waitFor(fn ($e) => in_array($e['type'] ?? null, ['RecordingFinished', 'RecordingFailed'], true)
        && ($e['recording']['name'] ?? null) === $name);

    $audio = $this->ari->recordings()->getStoredFile($name);
    expect(substr($audio, 0, 4))->toBe('RIFF')
        ->and(substr($audio, 8, 4))->toBe('WAVE');

    $this->ari->recordings()->copyStored($name, $copy);
    expect($this->ari->recordings()->getStored($copy))->toHaveKey('name', $copy);

    $this->ari->recordings()->deleteStored($copy);
    $this->ari->recordings()->deleteStored($name);

    expect(fn () => $this->ari->recordings()->getStoredFile($name))->toThrow(AriException::class);
});

it('snoops a channel without whisper and continues it in the dialplan', function () {
    $channelId = vmtestOriginate($this, 'snoop');
    $snoopId = 'vmtest-snoop-'.bin2hex(random_bytes(4));
    $this->cleanup[] = fn () => $this->ari->channels()->hangup($snoopId);

    $snoop = $this->ari->channels()->snoop($channelId, VMTEST_APP, spy: 'in', appArgs: 'snoop', snoopId: $snoopId);
    expect($snoop['id'])->toBe($snoopId);
    $this->events->waitFor(vmtestEventOf('StasisStart', $snoopId));
    $this->ari->channels()->hangup($snoopId);

    [$context, $extension, $priority] = explode(',', getenv('ARI_TEST_CONTINUE') ?: 'internal,*43,1');
    $this->ari->channels()->continueInDialplan($channelId, $context, $extension, (int) $priority);

    $this->events->waitFor(vmtestEventOf('StasisEnd', $channelId));
});

it('subscribes and unsubscribes an application to an event source', function () {
    $channelId = vmtestOriginate($this, 'sub');
    $source = 'endpoint:PJSIP/vmtest-none';

    try {
        $this->ari->applications()->subscribe(VMTEST_APP, "channel:{$channelId}");
    } catch (AriException $e) {
        $this->fail('subscribe failed: '.$e->getMessage());
    }

    expect($this->ari->applications()->get(VMTEST_APP)['channel_ids'])->toContain($channelId);

    $this->ari->applications()->unsubscribe(VMTEST_APP, "channel:{$channelId}");

    expect(fn () => $this->ari->applications()->subscribe(VMTEST_APP, $source))->toThrow(AriException::class);
});

it('creates and hangs up a server websocket external media channel', function () {
    $channelId = 'vmtest-em-'.bin2hex(random_bytes(4));
    $this->cleanup[] = fn () => $this->ari->channels()->hangup($channelId);

    $channel = $this->ari->channels()->externalMedia(
        app: VMTEST_APP,
        format: 'ulaw',
        channelId: $channelId,
        encapsulation: 'none',
        transport: 'websocket',
        connectionType: 'server',
    );

    expect($channel['id'])->toBe($channelId);

    $this->ari->channels()->hangup($channelId);
    expect(fn () => $this->ari->channels()->get($channelId))->toThrow(AriException::class);
});

it('reads asterisk variables and module state', function () {
    $variable = 'VMTEST_'.strtoupper(bin2hex(random_bytes(3)));
    $this->ari->asterisk()->setVariable($variable, 'ok');
    $this->cleanup[] = fn () => $this->ari->asterisk()->setVariable($variable, '');

    expect($this->ari->asterisk()->getModule('res_ari.so'))->toHaveKey('name', 'res_ari.so')
        ->and($this->ari->asterisk()->getVariable($variable))->toBe(['value' => 'ok']);
});

it('calls ARI asynchronously on the event loop', function () {
    $client = new AsyncAriClient($this->config['host'], $this->config['port'], $this->config['user'], $this->config['password'], timeout: 5);

    $info = vmtestAwait($client->get('asterisk/info', ['only' => 'system']));
    expect($info)->toHaveKey('system');

    expect(fn () => vmtestAwait($client->get('channels/vmtest-missing')))->toThrow(AriException::class);
});
