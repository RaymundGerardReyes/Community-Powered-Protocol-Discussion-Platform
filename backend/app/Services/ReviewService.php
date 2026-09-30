<?php

namespace App\Services;

use App\Models\Protocol;
use App\Models\Review;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class ReviewService
{
    /**
     * Submit a peer review for a protocol.
     *
     * @param  array<string, mixed>  $data
     *
     * @throws ValidationException
     */
    public function create(User $user, Protocol $protocol, array $data): Review
    {
        if ($protocol->user_id === $user->id) {
            throw ValidationException::withMessages([
                'protocol_id' => ['Authors cannot review their own protocols.'],
            ]);
        }

        $existing = Review::where('protocol_id', $protocol->id)
            ->where('user_id', $user->id)
            ->exists();

        if ($existing) {
            throw ValidationException::withMessages([
                'protocol_id' => ['You have already submitted a review for this protocol.'],
            ]);
        }

        return DB::transaction(function () use ($user, $protocol, $data) {
            $rating = (int) $data['rating'];
            $verdict = $data['verdict'] ?? ($rating >= 4 ? 'approved' : ($rating >= 2 ? 'changes_requested' : 'rejected'));
            $feedback = $data['feedback'] ?? null;
            $summary = $data['summary'] ?? ($feedback ? \Illuminate\Support\Str::limit($feedback, 250) : "Rating: {$rating}/5 stars");
            $findings = $data['findings'] ?? $feedback ?? null;

            $review = Review::create([
                'protocol_id' => $protocol->id,
                'user_id' => $user->id,
                'rating' => $rating,
                'verdict' => $verdict,
                'summary' => $summary,
                'findings' => $findings,
            ]);

            Log::info('review.created', [
                'review_id' => $review->id,
                'protocol_id' => $protocol->id,
                'user_id' => $user->id,
                'rating' => $review->rating,
                'verdict' => $review->verdict,
            ]);

            return $review;
        });
    }

    /**
     * Update an existing peer review.
     *
     * @param  array<string, mixed>  $data
     *
     * @throws AuthorizationException
     */
    public function update(Review $review, User $user, array $data): Review
    {
        if ($review->user_id !== $user->id) {
            throw new AuthorizationException('You are not authorized to update this review.');
        }

        return DB::transaction(function () use ($review, $data) {
            $review->update($data);

            Log::info('review.updated', [
                'review_id' => $review->id,
                'user_id' => $review->user_id,
            ]);

            return $review;
        });
    }

    /**
     * Delete a review.
     *
     * @throws AuthorizationException
     */
    public function delete(Review $review, User $user): bool
    {
        if ($review->user_id !== $user->id) {
            throw new AuthorizationException('You are not authorized to delete this review.');
        }

        return DB::transaction(function () use ($review, $user) {
            $id = $review->id;
            $deleted = (bool) $review->delete();

            Log::info('review.deleted', [
                'review_id' => $id,
                'user_id' => $user->id,
            ]);

            return $deleted;
        });
    }
}
