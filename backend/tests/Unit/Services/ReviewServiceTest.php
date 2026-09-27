<?php

namespace Tests\Unit\Services;

use App\Models\Protocol;
use App\Models\Review;
use App\Models\User;
use App\Services\ReviewService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class ReviewServiceTest extends TestCase
{
    use RefreshDatabase;

    protected ReviewService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(ReviewService::class);
    }

    public function test_can_create_peer_review_for_protocol(): void
    {
        $author = User::factory()->create();
        $reviewer = User::factory()->create();
        $protocol = Protocol::factory()->create(['user_id' => $author->id]);

        $review = $this->service->create($reviewer, $protocol, [
            'rating' => 5,
            'verdict' => 'approved',
            'summary' => 'Excellent architecture and solid proofs.',
            'findings' => 'No major security vulnerabilities found.',
        ]);

        $this->assertSame($protocol->id, $review->protocol_id);
        $this->assertSame($reviewer->id, $review->user_id);
        $this->assertSame(5, $review->rating);
        $this->assertSame('approved', $review->verdict);
        $this->assertSame('Excellent architecture and solid proofs.', $review->summary);
        $this->assertDatabaseHas('reviews', ['id' => $review->id, 'rating' => 5]);
    }

    public function test_author_cannot_review_own_protocol(): void
    {
        $this->expectException(ValidationException::class);

        $author = User::factory()->create();
        $protocol = Protocol::factory()->create(['user_id' => $author->id]);

        $this->service->create($author, $protocol, [
            'rating' => 5,
            'verdict' => 'approved',
            'summary' => 'Self praise review',
        ]);
    }

    public function test_user_cannot_submit_duplicate_review_for_same_protocol(): void
    {
        $this->expectException(ValidationException::class);

        $author = User::factory()->create();
        $reviewer = User::factory()->create();
        $protocol = Protocol::factory()->create(['user_id' => $author->id]);

        Review::factory()->create([
            'protocol_id' => $protocol->id,
            'user_id' => $reviewer->id,
        ]);

        $this->service->create($reviewer, $protocol, [
            'rating' => 4,
            'verdict' => 'approved',
            'summary' => 'Attempting second review',
        ]);
    }

    public function test_reviewer_can_update_own_review(): void
    {
        $author = User::factory()->create();
        $reviewer = User::factory()->create();
        $protocol = Protocol::factory()->create(['user_id' => $author->id]);
        $review = Review::factory()->create([
            'protocol_id' => $protocol->id,
            'user_id' => $reviewer->id,
            'rating' => 4,
        ]);

        $updated = $this->service->update($review, $reviewer, [
            'rating' => 5,
            'summary' => 'Updated review after revision.',
        ]);

        $this->assertSame(5, $updated->rating);
        $this->assertSame('Updated review after revision.', $updated->summary);
    }

    public function test_non_author_cannot_update_review(): void
    {
        $this->expectException(AuthorizationException::class);

        $reviewer = User::factory()->create();
        $otherUser = User::factory()->create();
        $review = Review::factory()->create(['user_id' => $reviewer->id]);

        $this->service->update($review, $otherUser, [
            'rating' => 1,
        ]);
    }

    public function test_reviewer_can_delete_own_review(): void
    {
        $reviewer = User::factory()->create();
        $review = Review::factory()->create(['user_id' => $reviewer->id]);

        $deleted = $this->service->delete($review, $reviewer);

        $this->assertTrue($deleted);
        $this->assertDatabaseMissing('reviews', ['id' => $review->id]);
    }

    public function test_non_author_cannot_delete_review(): void
    {
        $this->expectException(AuthorizationException::class);

        $reviewer = User::factory()->create();
        $otherUser = User::factory()->create();
        $review = Review::factory()->create(['user_id' => $reviewer->id]);

        $this->service->delete($review, $otherUser);
    }
}
