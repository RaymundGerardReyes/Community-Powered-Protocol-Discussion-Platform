<?php

namespace Tests\Feature\Console;

use App\Events\VoteCast;
use App\Models\Protocol;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class ReindexSearchCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_reindex_command_runs_and_reports_completion_status(): void
    {
        $this->artisan('search:reindex')
            ->expectsOutputToContain('Re-indexing Protocol...')
            ->expectsOutputToContain('Re-indexing Thread...')
            ->expectsOutputToContain('Search reindexing process completed.')
            ->assertSuccessful();
    }

    public function test_reindex_command_supports_filtering_by_specific_model(): void
    {
        $this->artisan('search:reindex', ['--model' => 'protocol'])
            ->expectsOutputToContain('Re-indexing Protocol...')
            ->doesntExpectOutput('Re-indexing Thread...')
            ->assertSuccessful();
    }

    public function test_sync_search_index_listener_is_attached_to_vote_cast_event_in_event_dispatcher(): void
    {
        Event::fake([VoteCast::class]);

        $user = User::factory()->create();
        $protocol = Protocol::factory()->create();

        event(new VoteCast($user, $protocol, 1, 'created'));

        Event::assertDispatched(VoteCast::class);
    }
}
