<?php

namespace App\Services;

use App\Models\Protocol;
use App\Models\Thread;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
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
        $thread->increment('views_count');
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
            $deleted = (bool) $thread->delete();

            Log::info('thread.deleted', [
                'thread_id' => $id,
                'user_id' => $user->id,
            ]);

            return $deleted;
        });
    }
}
