<?php

namespace Tests\Unit\Observers;

use App\Models\Protocol;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReviewObserverTest extends TestCase
{
    use RefreshDatabase;

    public function test_creating_review_automatically_recalculates_protocol_stats(): void
    {
        $author = User::factory()->create();
        $reviewer1 = User::factory()->create();
        $reviewer2 = User::factory()->create();

        $protocol = Protocol::factory()->create([
            'user_id' => $author->id,
            'reviews_count' => 0,
            'average_rating' => 0.00,
        ]);

        Review::factory()->create([
            'protocol_id' => $protocol->id,
            'user_id' => $reviewer1->id,
            'rating' => 5,
        ]);

        $this->assertSame(1, $protocol->fresh()->reviews_count);
        $this->assertEquals(5.00, $protocol->fresh()->average_rating);

        Review::factory()->create([
            'protocol_id' => $protocol->id,
            'user_id' => $reviewer2->id,
            'rating' => 4,
        ]);

        $this->assertSame(2, $protocol->fresh()->reviews_count);
        $this->assertEquals(4.50, $protocol->fresh()->average_rating);
    }

    public function test_updating_review_rating_recalculates_average_rating(): void
    {
        $author = User::factory()->create();
        $reviewer = User::factory()->create();

        $protocol = Protocol::factory()->create(['user_id' => $author->id]);

        $review = Review::factory()->create([
            'protocol_id' => $protocol->id,
            'user_id' => $reviewer->id,
            'rating' => 3,
        ]);

        $this->assertEquals(3.00, $protocol->fresh()->average_rating);

        $review->update(['rating' => 5]);

        $this->assertEquals(5.00, $protocol->fresh()->average_rating);
    }

    public function test_deleting_review_recalculates_stats_back_to_zero_if_empty(): void
    {
        $author = User::factory()->create();
        $reviewer = User::factory()->create();

        $protocol = Protocol::factory()->create(['user_id' => $author->id]);

        $review = Review::factory()->create([
            'protocol_id' => $protocol->id,
            'user_id' => $reviewer->id,
            'rating' => 4,
        ]);

        $this->assertSame(1, $protocol->fresh()->reviews_count);

        $review->delete();

        $fresh = $protocol->fresh();
        $this->assertSame(0, $fresh->reviews_count);
        $this->assertEquals(0.00, $fresh->average_rating);
    }
}
