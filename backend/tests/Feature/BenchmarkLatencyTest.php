<?php

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('track response time middleware attaches X-Response-Time and Server-Timing headers', function () {
    $response = $this->getJson('/api/v1/protocols');

    $response->assertOk();
    $response->assertHeader('X-Response-Time');
    $response->assertHeader('Server-Timing');

    expect($response->headers->get('X-Response-Time'))->toMatch('/^[0-9]+(\.[0-9]+)?ms$/');
    expect($response->headers->get('Server-Timing'))->toContain('app;dur=');
});

test('benchmark latency artisan command executes and reports metrics', function () {
    $this->artisan('benchmark:latency', [
        '--endpoint' => '/api/v1/protocols',
        '--count' => 10,
    ])->assertSuccessful();
});
