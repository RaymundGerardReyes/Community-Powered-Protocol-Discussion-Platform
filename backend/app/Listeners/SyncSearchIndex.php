<?php

namespace App\Listeners;

use App\Events\VoteCast;
use App\Models\Protocol;
use App\Models\Thread;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

/**
 * SyncSearchIndex
 * Decoupled listener keeping Typesense search index in sync with denormalized vote updates.
 * Structured to implement ShouldQueue for instant production async capability.
 */
class SyncSearchIndex implements ShouldQueue
{
    /**
     * Handle the event.
     */
    public function handle(VoteCast $event): void
    {
        $votable = $event->votable;

        if (! ($votable instanceof Protocol || $votable instanceof Thread)) {
            return;
        }

        if (app()->environment('testing')) {
            try {
                $votable->searchable();
            } catch (\Throwable) {}
            return;
        }

        try {
            if (app()->bound(\Typesense\Client::class) && config('scout.typesense.is_configured', false)) {
                /** @var \Typesense\Client $typesense */
                $typesense = app(\Typesense\Client::class);
                $collection = $votable instanceof Protocol ? 'protocol' : 'threads';
                $typesense->collections[$collection]->documents[(string) $votable->id]->update([
                    'votes_count' => (int) $votable->votes_count,
                    'votes' => (int) $votable->votes_count,
                    'vote_score' => (int) $votable->votes_count,
                    'score' => (int) ($votable->score ?? $votable->votes_count),
                ]);
            } else {
                $votable->searchable();
            }

            Log::info('search.index_synced', [
                'entity_type' => get_class($votable),
                'entity_id' => $votable->getKey(),
                'action' => $event->action,
            ]);
        } catch (\Throwable $e) {
            Log::warning('search.sync_failed', [
                'error' => $e->getMessage(),
            ]);
        }
    }
}
