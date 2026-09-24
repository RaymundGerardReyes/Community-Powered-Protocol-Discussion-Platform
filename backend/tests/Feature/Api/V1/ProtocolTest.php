<?php

test('can retrieve paginated protocols list', function () {
    $response = $this->getJson('/api/v1/protocols');

    $response->assertStatus(200);
});

test('protocol can be created with valid payload', function () {
    $payload = [
        'title' => 'Test Protocol',
        'content' => 'Comprehensive protocol documentation.',
        'tags' => ['consensus', 'layer2'],
    ];

    $response = $this->postJson('/api/v1/protocols', $payload);

    $response->assertStatus(201);
});
