<?php

use App\Events\VoteCast;
use App\Listeners\SyncSearchIndex;
use App\Models\Protocol;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;

uses(RefreshDatabase::class);

test('reindex command runs and reports completion status', function () {
    $this->artisan('search:reindex')
        ->expectsOutputToContain('Re-indexing Protocol...')
        ->expectsOutputToContain('Re-indexing Thread...')
        ->expectsOutputToContain('Search reindexing process completed.')
        ->assertSuccessful();
});

test('reindex command supports filtering by specific model', function () {
    $this->artisan('search:reindex', ['--model' => 'protocol'])
        ->expectsOutputToContain('Re-indexing Protocol...')
        ->doesntExpectOutput('Re-indexing Thread...')
        ->assertSuccessful();
});

test('SyncSearchIndex listener is attached to VoteCast event in event dispatcher', function () {
    Event::fake([VoteCast::class]);

    $user = User::factory()->create();
    $protocol = Protocol::factory()->create();

    event(new VoteCast($user, $protocol, 1, 'created'));

    Event::assertDispatched(VoteCast::class);
});
