<?php

namespace App\Observers;

use App\Models\Protocol;
use App\Models\Review;
use Illuminate\Support\Facades\Log;

class ReviewObserver
{
    /**
     * Handle the Review "saved" event (created or updated).
     */
    public function saved(Review $review): void
    {
        $this->recalculateProtocolReviewStats($review->protocol_id);
    }

    /**
     * Handle the Review "deleted" event.
     */
    public function deleted(Review $review): void
    {
        $this->recalculateProtocolReviewStats($review->protocol_id);
    }

    /**
     * Atomically recalculate review counts and average rating on parent protocol.
     */
    protected function recalculateProtocolReviewStats(int $protocolId): void
    {
        $protocol = Protocol::find($protocolId);
        if (! $protocol) {
            return;
        }

        $reviewsCount = $protocol->reviews()->count();
        $averageRating = $reviewsCount > 0
            ? round((float) $protocol->reviews()->avg('rating'), 2)
            : 0.00;

        $protocol->update([
            'reviews_count' => $reviewsCount,
            'average_rating' => $averageRating,
        ]);

        Log::info('protocol.reviews_recalculated', [
            'protocol_id' => $protocolId,
            'reviews_count' => $reviewsCount,
            'average_rating' => $averageRating,
        ]);
    }
}
