<?php

namespace Tests\E2E;

use App\Models\Protocol;
use App\Models\Thread;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProtocolLifecycleE2ETest extends TestCase
{
    use RefreshDatabase;

    public function test_complete_protocol_governance_and_discussion_lifecycle(): void
    {
        // -------------------------------------------------------------
        // Phase 1: User Onboarding & Personas
        // -------------------------------------------------------------
        $author = User::factory()->create(['name' => 'Alice Author', 'email' => 'alice@protocol.test']);
        $reviewerA = User::factory()->create(['name' => 'Bob Auditor', 'email' => 'bob@audit.test']);
        $reviewerB = User::factory()->create(['name' => 'Charlie Reviewer', 'email' => 'charlie@audit.test']);
        $contributor = User::factory()->create(['name' => 'Dave Contributor', 'email' => 'dave@community.test']);
        $voter = User::factory()->create(['name' => 'Eve Voter', 'email' => 'eve@voter.test']);

        // -------------------------------------------------------------
        // Phase 2: Protocol Authoring, Updating, and Publishing
        // -------------------------------------------------------------
        // 1. Author posts draft protocol
        $createResponse = $this->actingAs($author, 'sanctum')
            ->postJson('/api/v1/protocols', [
                'title' => 'Decentralized Sequencer Coordination Standard',
                'description' => 'Specification for threshold signature decentralized sequencers.',
                'category' => 'Layer 2',
                'version' => '1.0.0',
                'status' => 'draft',
            ]);

        $createResponse->assertStatus(201)
            ->assertJsonPath('data.title', 'Decentralized Sequencer Coordination Standard')
            ->assertJsonPath('data.status', 'draft')
            ->assertJsonPath('data.slug', 'decentralized-sequencer-coordination-standard');

        $protocolId = $createResponse->json('data.id');
        $protocolSlug = $createResponse->json('data.slug');

        // 2. Draft is not visible in default public published listing
        $publicList = $this->getJson('/api/v1/protocols');
        $publicList->assertStatus(200);
        $this->assertCount(0, $publicList->json('data'));

        // 3. Author updates draft protocol
        $updateResponse = $this->actingAs($author, 'sanctum')
            ->putJson("/api/v1/protocols/{$protocolId}", [
                'description' => 'Enhanced specification for fault-tolerant decentralized sequencers.',
            ]);

        $updateResponse->assertStatus(200)
            ->assertJsonPath('data.description', 'Enhanced specification for fault-tolerant decentralized sequencers.');

        // 4. Non-author cannot update or publish the protocol
        $unauthorizedPublish = $this->actingAs($voter, 'sanctum')
            ->putJson("/api/v1/protocols/{$protocolId}", [
                'status' => 'published',
            ]);
        $unauthorizedPublish->assertStatus(403);

        // 5. Author publishes the protocol
        $publishResponse = $this->actingAs($author, 'sanctum')
            ->putJson("/api/v1/protocols/{$protocolId}", [
                'status' => 'published',
            ]);

        $publishResponse->assertStatus(200)
            ->assertJsonPath('data.status', 'published');

        // 6. Protocol is now visible on public listing
        $publicListAfterPublish = $this->getJson('/api/v1/protocols');
        $publicListAfterPublish->assertStatus(200);
        $this->assertCount(1, $publicListAfterPublish->json('data'));

        // -------------------------------------------------------------
        // Phase 3: Peer Review & Atomic Rating Recalculation
        // -------------------------------------------------------------
        // 1. Author self-review is blocked
        $selfReview = $this->actingAs($author, 'sanctum')
            ->postJson("/api/v1/protocols/{$protocolId}/reviews", [
                'rating' => 5,
                'verdict' => 'approved',
                'summary' => 'Self endorsement attempt.',
            ]);
        $selfReview->assertStatus(422)
            ->assertJsonValidationErrors(['protocol_id']);

        // 2. Reviewer A posts positive review (5 stars)
        $review1 = $this->actingAs($reviewerA, 'sanctum')
            ->postJson("/api/v1/protocols/{$protocolId}/reviews", [
                'rating' => 5,
                'verdict' => 'approved',
                'summary' => 'Formally verified threshold proofs are sound.',
            ]);
        $review1->assertStatus(201);

        $freshProtocol = Protocol::find($protocolId);
        $this->assertSame(1, $freshProtocol->reviews_count);
        $this->assertEquals(5.00, $freshProtocol->average_rating);

        // 3. Reviewer B posts moderate review (3 stars)
        $review2 = $this->actingAs($reviewerB, 'sanctum')
            ->postJson("/api/v1/protocols/{$protocolId}/reviews", [
                'rating' => 3,
                'verdict' => 'changes_requested',
                'summary' => 'Gas costs for threshold verification exceed L1 block targets.',
            ]);
        $review2->assertStatus(201);

        $freshProtocol = Protocol::find($protocolId);
        $this->assertSame(2, $freshProtocol->reviews_count);
        $this->assertEquals(4.00, $freshProtocol->average_rating);

        // 4. Duplicate review attempt from Reviewer A is rejected
        $duplicateReview = $this->actingAs($reviewerA, 'sanctum')
            ->postJson("/api/v1/protocols/{$protocolId}/reviews", [
                'rating' => 4,
                'verdict' => 'approved',
                'summary' => 'Duplicate attempt.',
            ]);
        $duplicateReview->assertStatus(422);

        // -------------------------------------------------------------
        // Phase 4: Discussion Threads & Recursive Multi-Level Comments
        // -------------------------------------------------------------
        // 1. Contributor opens a discussion thread on the protocol
        $threadResponse = $this->actingAs($contributor, 'sanctum')
            ->postJson("/api/v1/protocols/{$protocolId}/threads", [
                'title' => 'Sequencer Latency Benchmarks',
                'content' => 'Benchmarking p2p signature aggregation across 50 global nodes.',
                'is_pinned' => true,
            ]);

        $threadResponse->assertStatus(201)
            ->assertJsonPath('data.title', 'Sequencer Latency Benchmarks')
            ->assertJsonPath('data.is_pinned', true);

        $threadId = $threadResponse->json('data.id');

        // 2. Author posts root comment on the thread
        $rootCommentResponse = $this->actingAs($author, 'sanctum')
            ->postJson("/api/v1/threads/{$threadId}/comments", [
                'content' => 'Initial benchmarks show sub-500ms latency on testnet.',
            ]);

        $rootCommentResponse->assertStatus(201)
            ->assertJsonPath('data.content', 'Initial benchmarks show sub-500ms latency on testnet.');

        $rootCommentId = $rootCommentResponse->json('data.id');

        // 3. Contributor posts nested reply to author's root comment
        $replyResponse = $this->actingAs($contributor, 'sanctum')
            ->postJson("/api/v1/threads/{$threadId}/comments", [
                'content' => 'Did testnet include Byzantine adversary simulations?',
                'parent_id' => $rootCommentId,
            ]);

        $replyResponse->assertStatus(201)
            ->assertJsonPath('data.parent_id', $rootCommentId);

        $replyId = $replyResponse->json('data.id');

        // 4. Voter posts deep nested reply (level 3)
        $deepReplyResponse = $this->actingAs($voter, 'sanctum')
            ->postJson("/api/v1/threads/{$threadId}/comments", [
                'content' => 'Fault tolerance was tested up to 33% malicious nodes.',
                'parent_id' => $replyId,
            ]);

        $deepReplyResponse->assertStatus(201)
            ->assertJsonPath('data.parent_id', $replyId);

        // 5. Verify thread replies counter updated atomically
        $freshThread = Thread::find($threadId);
        $this->assertSame(3, $freshThread->replies_count);

        // -------------------------------------------------------------
        // Phase 5: Polymorphic Voting Engine (Vote, Flip, and Toggle)
        // -------------------------------------------------------------
        // 1. Voter casts UPVOTE on Protocol (+1)
        $protocolVote = $this->actingAs($voter, 'sanctum')
            ->postJson('/api/v1/votes', [
                'votable_type' => 'protocol',
                'votable_id' => $protocolId,
                'value' => 1,
            ]);

        $protocolVote->assertStatus(200)
            ->assertJsonPath('data.action', 'created')
            ->assertJsonPath('data.current_vote', 1)
            ->assertJsonPath('data.votes_count', 1);

        // Score with 1 vote and 2 reviews = (1 * 10) + (2 * 5) = 20
        $this->assertSame(20, Protocol::find($protocolId)->score);

        // 2. Voter FLIPS vote on Protocol from +1 to -1
        $flipVote = $this->actingAs($voter, 'sanctum')
            ->postJson('/api/v1/votes', [
                'votable_type' => 'protocol',
                'votable_id' => $protocolId,
                'value' => -1,
            ]);

        $flipVote->assertStatus(200)
            ->assertJsonPath('data.action', 'updated')
            ->assertJsonPath('data.current_vote', -1)
            ->assertJsonPath('data.votes_count', -1);

        // Score with -1 vote and 2 reviews = max(0, (-1 * 10) + (2 * 5)) = 0
        $this->assertSame(0, Protocol::find($protocolId)->score);

        // 3. Voter TOGGLES SAME VOTE (-1) to REMOVE it (neutralize)
        $removeVote = $this->actingAs($voter, 'sanctum')
            ->postJson('/api/v1/votes', [
                'votable_type' => 'protocol',
                'votable_id' => $protocolId,
                'value' => -1,
            ]);

        $removeVote->assertStatus(200)
            ->assertJsonPath('data.action', 'removed')
            ->assertJsonPath('data.current_vote', 0)
            ->assertJsonPath('data.votes_count', 0);

        // Score with 0 votes and 2 reviews = max(0, 0 + 10) = 10
        $this->assertSame(10, Protocol::find($protocolId)->score);

        // 4. Voter upvotes the Thread
        $threadVote = $this->actingAs($voter, 'sanctum')
            ->postJson('/api/v1/votes', [
                'votable_type' => 'thread',
                'votable_id' => $threadId,
                'value' => 1,
            ]);

        $threadVote->assertStatus(200)
            ->assertJsonPath('data.current_vote', 1)
            ->assertJsonPath('data.votes_count', 1);

        $this->assertSame(1, Thread::find($threadId)->votes_count);

        // -------------------------------------------------------------
        // Phase 6: Final Public State & Architectural Integrity Check
        // -------------------------------------------------------------
        $detailResponse = $this->getJson("/api/v1/protocols/{$protocolSlug}");
        $detailResponse->assertStatus(200)
            ->assertJsonPath('data.title', 'Decentralized Sequencer Coordination Standard')
            ->assertJsonPath('data.status', 'published')
            ->assertJsonPath('data.reviews_count', 2)
            ->assertJsonPath('data.average_rating', 4);

        $threadsResponse = $this->getJson("/api/v1/protocols/{$protocolId}/threads");
        $threadsResponse->assertStatus(200);
        $this->assertCount(1, $threadsResponse->json('data'));
        $this->assertSame(3, $threadsResponse->json('data.0.replies_count'));
    }
}
