<?php

namespace App\Services;

use App\Events\VoteCast;
use App\Models\Comment;
use App\Models\Protocol;
use App\Models\Thread;
use App\Models\User;
use App\Models\Vote;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class VoteService
{
    /**
     * Map of alias / shorthand to fully qualified model classes.
     *
     * @var array<string, class-string<Model>>
     */
    protected array $allowedTypes = [
        'protocol' => Protocol::class,
        'thread' => Thread::class,
        'comment' => Comment::class,
        Protocol::class => Protocol::class,
        Thread::class => Thread::class,
        Comment::class => Comment::class,
    ];

    /**
     * Cast or toggle a vote on a target entity.
     *
     * @return array{action: string, current_vote: int, votes_count: int}
     *
     * @throws ValidationException|ModelNotFoundException
     */
    public function cast(User $user, string $type, int $id, int $value): array
    {
        $resolvedClass = $this->allowedTypes[$type] ?? null;

        if (! $resolvedClass) {
            throw ValidationException::withMessages([
                'votable_type' => ['Invalid votable type specified. Allowed types: protocol, thread, comment.'],
            ]);
        }

        /** @var Model $votable */
        $votable = $resolvedClass::find($id);

        if (! $votable) {
            throw (new ModelNotFoundException)->setModel($resolvedClass, [$id]);
        }

        return DB::transaction(function () use ($user, $votable, $resolvedClass, $id, $value) {
            $existingVote = Vote::where('user_id', $user->id)
                ->where('votable_type', $resolvedClass)
                ->where('votable_id', $id)
                ->first();

            if ($existingVote && $existingVote->value === $value) {
                // Toggle off: remove existing vote
                $existingVote->delete();
                $action = 'removed';
                $currentVote = 0;
            } elseif ($existingVote) {
                // Update existing vote value
                $existingVote->update(['value' => $value]);
                $action = 'updated';
                $currentVote = $value;
            } else {
                // Insert new vote
                Vote::create([
                    'user_id' => $user->id,
                    'votable_type' => $resolvedClass,
                    'votable_id' => $id,
                    'value' => $value,
                ]);
                $action = 'created';
                $currentVote = $value;
            }

            // Recalculate denormalized votes_count
            $totalVotes = (int) $votable->votes()->sum('value');
            $updateAttributes = ['votes_count' => $totalVotes];

            if ($votable instanceof Protocol) {
                $updateAttributes['score'] = max(0, ($totalVotes * 10) + ($votable->reviews_count * 5));
            }

            $votable->update($updateAttributes);

            // Dispatch domain event for listeners and search index sync
            event(new VoteCast($user, $votable, $currentVote, $action));

            // Structured logging
            Log::info('vote.cast', [
                'user_id' => $user->id,
                'votable_type' => $resolvedClass,
                'votable_id' => $id,
                'action' => $action,
                'current_vote' => $currentVote,
                'votes_count' => $totalVotes,
            ]);

            return [
                'action' => $action,
                'current_vote' => $currentVote,
                'votes_count' => $totalVotes,
            ];
        });
    }
}
