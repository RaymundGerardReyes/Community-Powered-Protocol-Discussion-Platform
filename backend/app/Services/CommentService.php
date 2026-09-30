<?php

namespace App\Services;

use App\Models\Comment;
use App\Models\Thread;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class CommentService
{
    /**
     * Post a comment or nested reply to a thread.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(User $user, Thread $thread, array $data): Comment
    {
        return DB::transaction(function () use ($user, $thread, $data) {
            // Verify parent comment belongs to the same thread if specified
            if (! empty($data['parent_id'])) {
                $parent = Comment::find($data['parent_id']);
                if (! ($parent instanceof Comment) || $parent->thread_id !== $thread->id) {
                    throw ValidationException::withMessages([
                        'parent_id' => ['The specified parent comment does not belong to this thread.'],
                    ]);
                }
            }

            $comment = Comment::create([
                'thread_id' => $thread->id,
                'user_id' => $user->id,
                'parent_id' => $data['parent_id'] ?? null,
                'content' => $data['content'],
            ]);

            $thread->increment('replies_count');

            Log::info('comment.created', [
                'comment_id' => $comment->id,
                'thread_id' => $thread->id,
                'user_id' => $user->id,
                'parent_id' => $comment->parent_id,
            ]);

            return $comment;
        });
    }

    /**
     * Update an existing comment.
     *
     * @param  array<string, mixed>  $data
     *
     * @throws AuthorizationException
     */
    public function update(Comment $comment, User $user, array $data): Comment
    {
        if ($comment->user_id !== $user->id) {
            throw new AuthorizationException('You are not authorized to update this comment.');
        }

        return DB::transaction(function () use ($comment, $data) {
            $comment->update($data);

            Log::info('comment.updated', [
                'comment_id' => $comment->id,
                'user_id' => $comment->user_id,
            ]);

            return $comment;
        });
    }

    /**
     * Delete a comment and decrement thread replies count.
     *
     * @throws AuthorizationException
     */
    public function delete(Comment $comment, User $user): bool
    {
        if ($comment->user_id !== $user->id) {
            throw new AuthorizationException('You are not authorized to delete this comment.');
        }

        return DB::transaction(function () use ($comment, $user) {
            $threadId = $comment->thread_id;
            $commentId = $comment->id;

            $deleted = (bool) $comment->delete();

            Thread::where('id', $threadId)->decrement('replies_count');

            Log::info('comment.deleted', [
                'comment_id' => $commentId,
                'thread_id' => $threadId,
                'user_id' => $user->id,
            ]);

            return $deleted;
        });
    }
}
