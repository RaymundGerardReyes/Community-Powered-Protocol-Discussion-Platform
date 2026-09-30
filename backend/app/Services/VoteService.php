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
use Illuminate\Support\Facades\Cache;
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

        /** @var Protocol|Thread|Comment|null $votable */
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

            if (method_exists($votable, 'withoutSyncingToSearch')) {
                $votable->withoutSyncingToSearch(function () use ($votable, $updateAttributes) {
                    $votable->update($updateAttributes);
                });
            } else {
                $votable->update($updateAttributes);
            }

            // Invalidate cache immediately so upcoming reads reflect the fresh vote counts
            if ($votable instanceof Protocol) {
                Cache::forget('typesense.protocol.doc.' . md5($votable->slug));
                Cache::forget('typesense.protocol.doc.' . md5((string) $votable->id));
            } elseif ($votable instanceof Thread) {
                Cache::forget('typesense.threads.p.' . $votable->protocol_id);
            }

            // Dispatch domain event for background search index synchronization
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

    /**
     * Get dictionary of all votes cast by the specified user indexed by entity type.
     *
     * @return array<string, array<string, int>>
     */
    public function getUserVotes(User $user): array
    {
        $votes = Vote::where('user_id', $user->id)->get();

        $map = [
            'protocol' => [],
            'thread' => [],
            'comment' => [],
        ];

        foreach ($votes as $vote) {
            $shorthand = match ($vote->votable_type) {
                Protocol::class, 'protocol' => 'protocol',
                Thread::class, 'thread' => 'thread',
                Comment::class, 'comment' => 'comment',
                default => null,
            };

            if ($shorthand) {
                $map[$shorthand][(string) $vote->votable_id] = (int) $vote->value;
            }
        }

        return $map;
    }
}
