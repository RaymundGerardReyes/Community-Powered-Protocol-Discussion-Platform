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
class SyncSearchIndex
{
    /**
     * Handle the event.
     */
    public function handle(VoteCast $event): void
    {
        $votable = $event->votable;

        // Re-index target entity in Scout / Typesense
        if ($votable instanceof Protocol || $votable instanceof Thread) {
            $votable->searchable();
        }

        // If a thread was voted on, also refresh parent protocol score in search index
        if ($votable instanceof Thread && $votable->protocol instanceof Protocol) {
            $votable->protocol->searchable();
        }

        Log::info('search.index_synced', [
            'entity_type' => get_class($votable),
            'entity_id' => $votable->getKey(),
            'action' => $event->action,
        ]);
    }
}
