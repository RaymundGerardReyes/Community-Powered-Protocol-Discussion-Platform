<?php

namespace App\Services;

use App\Models\Protocol;
use App\Models\Thread;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ThreadService
{
    /**
     * Create a new discussion thread for a protocol.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(User $user, Protocol $protocol, array $data): Thread
    {
        return DB::transaction(function () use ($user, $protocol, $data) {
            $baseSlug = Str::slug($data['title']);
            $slug = $baseSlug.'-'.Str::random(6);

            $thread = Thread::create([
                'protocol_id' => $protocol->id,
                'user_id' => $user->id,
                'title' => $data['title'],
                'slug' => $slug,
                'content' => $data['content'],
                'is_pinned' => $data['is_pinned'] ?? false,
            ]);

            Cache::forget('typesense.threads.p.' . $protocol->id);
            Cache::forget('typesense.protocol.doc.' . md5($protocol->slug));
            Cache::forget('typesense.protocol.doc.' . md5((string) $protocol->id));

            Log::info('thread.created', [
                'thread_id' => $thread->id,
                'protocol_id' => $protocol->id,
                'user_id' => $user->id,
            ]);

            return $thread;
        });
    }

    /**
     * Increment views counter.
     */
    public function incrementViews(Thread $thread): void
    {
        $thread->views_count = ($thread->views_count ?? 0) + 1;

        $driver = (string) config('scout.driver', 'null');
        $isTypesense = $driver === 'typesense' || str_starts_with($driver, 'ty');

        if ($isTypesense && app()->bound(\Typesense\Client::class)) {
            try {
                /** @var \Typesense\Client $typesense */
                $typesense = app(\Typesense\Client::class);
                $typesense->collections['threads']->documents[(string) $thread->id]->update([
                    'views_count' => (int) $thread->views_count,
                ]);
            } catch (\Throwable) {
                // Silently continue if Typesense update fails or is in mock testing
            }
            return;
        }

        if ($thread->exists && $thread->getKey()) {
            try {
                $thread->increment('views_count');
            } catch (\Throwable) {
                // Silently ignore if database is decoupled
            }
        }
    }

    /**
     * Update an existing thread.
     *
     * @param  array<string, mixed>  $data
     *
     * @throws AuthorizationException
     */
    public function update(Thread $thread, User $user, array $data): Thread
    {
        if ($thread->user_id !== $user->id) {
            throw new AuthorizationException('You are not authorized to update this thread.');
        }

        $thread->update($data);

        Cache::forget('typesense.threads.p.' . $thread->protocol_id);

        Log::info('thread.updated', [
            'thread_id' => $thread->id,
            'user_id' => $user->id,
        ]);

        return $thread;
    }

    /**
     * Delete a thread.
     *
     * @throws AuthorizationException
     */
    public function delete(Thread $thread, User $user): bool
    {
        if ($thread->user_id !== $user->id) {
            throw new AuthorizationException('You are not authorized to delete this thread.');
        }

        return DB::transaction(function () use ($thread, $user) {
            $id = $thread->id;
            $protocolId = $thread->protocol_id;
            $deleted = (bool) $thread->delete();

            Cache::forget('typesense.threads.p.' . $protocolId);

            Log::info('thread.deleted', [
                'thread_id' => $id,
                'user_id' => $user->id,
            ]);

            return $deleted;
        });
    }
}
