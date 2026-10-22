<?php

use VoIPforAll\AsteriskAri\Contracts\AriClientInterface;
use VoIPforAll\AsteriskAri\Exceptions\AriConnectionException;
use VoIPforAll\AsteriskAri\Resources\Asterisk;

beforeEach(function () {
    $this->client = Mockery::mock(AriClientInterface::class);
    $this->asterisk = new Asterisk($this->client);
});

it('gets full asterisk info', function () {
    $this->client->shouldReceive('get')
        ->with('asterisk/info', [])
        ->once()
        ->andReturn(['build' => [], 'system' => [], 'config' => []]);

    $result = $this->asterisk->info();

    expect($result)->toHaveKeys(['build', 'system', 'config']);
});

it('gets filtered asterisk info', function () {
    $this->client->shouldReceive('get')
        ->with('asterisk/info', ['only' => 'system'])
        ->once()
        ->andReturn(['system' => ['entity_id' => 'abc']]);

    $result = $this->asterisk->info('system');

    expect($result)->toHaveKey('system');
});

it('pings successfully', function () {
    $this->client->shouldReceive('get')
        ->with('asterisk/info', ['only' => 'system'])
        ->once()
        ->andReturn(['system' => []]);

    expect($this->asterisk->ping())->toBeTrue();
});

it('ping returns false on failure', function () {
    $this->client->shouldReceive('get')
        ->with('asterisk/info', ['only' => 'system'])
        ->once()
        ->andThrow(new AriConnectionException('Connection refused'));

    expect($this->asterisk->ping())->toBeFalse();
});

it('gets a global variable', function () {
    $this->client->shouldReceive('get')
        ->with('asterisk/variable', ['variable' => 'GLOBAL_VAR'])
        ->once()
        ->andReturn(['value' => 'hello']);

    $result = $this->asterisk->getVariable('GLOBAL_VAR');

    expect($result)->toHaveKey('value', 'hello');
});

it('sets a global variable', function () {
    $this->client->shouldReceive('post')
        ->with('asterisk/variable', ['variable' => 'VM_FLAG', 'value' => '1'])
        ->once()
        ->andReturn([]);

    $this->asterisk->setVariable('VM_FLAG', '1');
});

it('lists modules', function () {
    $this->client->shouldReceive('get')
        ->with('asterisk/modules')
        ->once()
        ->andReturn([['name' => 'res_pjsip.so']]);

    expect($this->asterisk->listModules())->toHaveCount(1);
});

it('gets a module', function () {
    $this->client->shouldReceive('get')
        ->with('asterisk/modules/res_pjsip.so')
        ->once()
        ->andReturn(['name' => 'res_pjsip.so', 'status' => 'Running']);

    expect($this->asterisk->getModule('res_pjsip.so'))->toHaveKey('status', 'Running');
});

it('loads a module', function () {
    $this->client->shouldReceive('post')
        ->with('asterisk/modules/app_queue.so')
        ->once()
        ->andReturn([]);

    $this->asterisk->loadModule('app_queue.so');
});

it('unloads a module', function () {
    $this->client->shouldReceive('delete')
        ->with('asterisk/modules/app_queue.so')
        ->once()
        ->andReturn([]);

    $this->asterisk->unloadModule('app_queue.so');
});

it('reloads a module', function () {
    $this->client->shouldReceive('put')
        ->with('asterisk/modules/res_pjsip.so')
        ->once()
        ->andReturn([]);

    $this->asterisk->reloadModule('res_pjsip.so');
});

it('rejects a traversing module name', function () {
    $this->client->shouldNotReceive('put');

    $this->asterisk->reloadModule('..');
})->throws(InvalidArgumentException::class);
