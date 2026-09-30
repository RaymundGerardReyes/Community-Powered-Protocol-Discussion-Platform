<?php

namespace Tests\Feature\Api\V1;

use App\Models\Comment;
use App\Models\Protocol;
use App\Models\Thread;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ThreadCommentApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['scout.driver' => 'null']);
    }

    protected function mockTypesenseClientForThreads(array $hits = [], int $found = 1, ?array $doc = null): void
    {
        $mockDocuments = $this->createMock(\Typesense\Documents::class);
        $mockDocuments->expects($this->any())
            ->method('search')
            ->willReturn([
                'found' => $found,
                'hits' => $hits,
            ]);

        if ($doc !== null) {
            $mockDocument = $this->createMock(\Typesense\Document::class);
            $mockDocument->expects($this->any())
                ->method('retrieve')
                ->willReturn($doc);

            $mockDocuments->expects($this->any())
                ->method('offsetGet')
                ->willReturn($mockDocument);
        }

        $mockCollection = $this->createMock(\Typesense\Collection::class);
        $mockCollection->documents = $mockDocuments;

        $mockCollections = $this->createMock(\Typesense\Collections::class);
        $mockCollections->expects($this->any())
            ->method('offsetGet')
            ->with('threads')
            ->willReturn($mockCollection);

        $client = new \Typesense\Client([
            'nodes' => [['host' => 'localhost', 'port' => '8108', 'protocol' => 'http']],
            'api_key' => 'test-key',
        ]);
        $client->collections = $mockCollections;

        $this->app->instance(\Typesense\Client::class, $client);
    }

    public function test_can_retrieve_paginated_threads_list_for_protocol(): void
    {
        $this->mockTypesenseClientForThreads([
            [
                'document' => [
                    'id' => '1',
                    'protocol_id' => 1,
                    'title' => 'Proposal Discussion for Tokenomics',
                    'slug' => 'proposal-discussion-for-tokenomics',
                    'content' => 'What are your thoughts on bonding curve parameters?',
                    'views_count' => 10,
                    'replies_count' => 2,
                    'author' => 'Alice Protocol',
                ],
            ],
        ], 1);

        $response = $this->getJson('/api/v1/protocols/1/threads');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'protocol_id', 'title', 'slug', 'content', 'body', 'views_count', 'replies_count'],
                ],
                'meta',
            ]);
    }

    public function test_can_view_a_thread_and_increment_its_view_count(): void
    {
        $user = User::factory()->create();
        $protocol = Protocol::factory()->create(['user_id' => $user->id]);
        $thread = Thread::factory()->create([
            'protocol_id' => $protocol->id,
            'user_id' => $user->id,
            'views_count' => 5,
        ]);

        $this->mockTypesenseClientForThreads([], 1, [
            'id' => (string) $thread->id,
            'protocol_id' => $protocol->id,
            'title' => $thread->title,
            'content' => $thread->content,
            'author' => $user->name,
            'views_count' => 5,
        ]);

        $response = $this->getJson("/api/v1/threads/{$thread->id}");

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'id' => $thread->id,
                    'title' => $thread->title,
                ],
            ]);

        $this->assertSame(6, $thread->fresh()->views_count);
    }

    public function test_authenticated_user_can_create_thread_in_protocol(): void
    {
        $user = User::factory()->create();
        $protocol = Protocol::factory()->create();

        $response = $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/protocols/{$protocol->id}/threads", [
                'title' => 'Proposal Discussion for Tokenomics',
                'content' => 'What are your thoughts on bonding curve parameters?',
            ]);

        $response->assertStatus(201)
            ->assertJson([
                'data' => [
                    'title' => 'Proposal Discussion for Tokenomics',
                    'protocol_id' => $protocol->id,
                ],
            ]);

        $this->assertDatabaseHas('threads', [
            'protocol_id' => $protocol->id,
            'user_id' => $user->id,
            'title' => 'Proposal Discussion for Tokenomics',
        ]);
    }

    public function test_authenticated_user_can_create_thread_using_body_field(): void
    {
        $user = User::factory()->create();
        $protocol = Protocol::factory()->create();

        $response = $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/protocols/{$protocol->id}/threads", [
                'title' => 'Cold Plunge Optimal Timing Discussion',
                'body' => 'Should cold plunge be performed before or after hypertrophy training?',
            ]);

        $response->assertStatus(201)
            ->assertJson([
                'data' => [
                    'title' => 'Cold Plunge Optimal Timing Discussion',
                    'protocol_id' => $protocol->id,
                    'body' => 'Should cold plunge be performed before or after hypertrophy training?',
                    'content' => 'Should cold plunge be performed before or after hypertrophy training?',
                ],
            ]);
    }

    public function test_authenticated_user_can_post_comment_and_nested_reply_to_thread(): void
    {
        $user = User::factory()->create();
        $protocol = Protocol::factory()->create();
        $thread = Thread::factory()->create([
            'protocol_id' => $protocol->id,
            'replies_count' => 0,
        ]);

        // Top-level comment
        $response = $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/threads/{$thread->id}/comments", [
                'content' => 'I strongly agree with the gas reduction section.',
            ]);

        $response->assertStatus(201)
            ->assertJson([
                'data' => [
                    'thread_id' => $thread->id,
                    'content' => 'I strongly agree with the gas reduction section.',
                ],
            ]);

        $commentId = $response->json('data.id');
        $this->assertSame(1, $thread->fresh()->replies_count);

        // Nested reply to top-level comment
        $replyResponse = $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/threads/{$thread->id}/comments", [
                'content' => 'Here is benchmark data supporting this.',
                'parent_id' => $commentId,
            ]);

        $replyResponse->assertStatus(201)
            ->assertJson([
                'data' => [
                    'parent_id' => $commentId,
                    'content' => 'Here is benchmark data supporting this.',
                ],
            ]);

        $this->assertSame(2, $thread->fresh()->replies_count);
    }

    public function test_user_cannot_reply_to_a_parent_comment_from_another_thread(): void
    {
        $user = User::factory()->create();
        $thread1 = Thread::factory()->create();
        $thread2 = Thread::factory()->create();

        $commentOnThread1 = Comment::factory()->create(['thread_id' => $thread1->id]);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/threads/{$thread2->id}/comments", [
                'content' => 'Mismatched reply',
                'parent_id' => $commentOnThread1->id,
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['parent_id']);
    }

    public function test_author_can_delete_their_comment_and_decrement_thread_replies_count(): void
    {
        $user = User::factory()->create();
        $thread = Thread::factory()->create(['replies_count' => 1]);
        $comment = Comment::factory()->create([
            'thread_id' => $thread->id,
            'user_id' => $user->id,
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->deleteJson("/api/v1/comments/{$comment->id}");

        $response->assertStatus(200);
        $this->assertDatabaseMissing('comments', ['id' => $comment->id]);
        $this->assertSame(0, $thread->fresh()->replies_count);
    }

    public function test_returns_recursively_assembled_comment_tree_with_arbitrary_depth_and_iso_timestamps(): void
    {
        $thread = Thread::factory()->create();

        $zuck = User::factory()->create(['name' => 'Mr. Zuckerberg']);
        $bezos = User::factory()->create(['name' => 'Mr. Bezos']);
        $musk = User::factory()->create(['name' => 'Mr. Musk']);
        $gates = User::factory()->create(['name' => 'Mr. Gates']);
        $jobs = User::factory()->create(['name' => 'Mr. Jobs']);

        // A: Zuckerberg (root)
        $commentA = Comment::factory()->create([
            'thread_id' => $thread->id,
            'user_id' => $zuck->id,
            'parent_id' => null,
            'content' => 'Original comment from Zuckerberg',
            'created_at' => '2026-09-27 15:14:42',
        ]);

        // B: Bezos (reply to A)
        $commentB = Comment::factory()->create([
            'thread_id' => $thread->id,
            'user_id' => $bezos->id,
            'parent_id' => $commentA->id,
            'content' => 'Reply from Bezos',
            'created_at' => '2026-09-27 15:15:07',
        ]);

        // C: Musk (reply to B)
        $commentC = Comment::factory()->create([
            'thread_id' => $thread->id,
            'user_id' => $musk->id,
            'parent_id' => $commentB->id,
            'content' => 'Reply from Musk',
            'created_at' => '2026-09-27 15:15:41',
        ]);

        // D: Gates (reply to C)
        $commentD = Comment::factory()->create([
            'thread_id' => $thread->id,
            'user_id' => $gates->id,
            'parent_id' => $commentC->id,
            'content' => 'Reply from Gates',
            'created_at' => '2026-09-27 15:16:03',
        ]);

        // E: Jobs (reply to A)
        $commentE = Comment::factory()->create([
            'thread_id' => $thread->id,
            'user_id' => $jobs->id,
            'parent_id' => $commentA->id,
            'content' => 'Reply from Jobs',
            'created_at' => '2026-09-27 15:17:12',
        ]);

        $response = $this->getJson("/api/v1/threads/{$thread->id}/comments");

        $response->assertStatus(200);

        $data = $response->json('data');
        $this->assertCount(1, $data);
        $this->assertSame('Mr. Zuckerberg', $data[0]['author']['name']);
        $this->assertStringContainsString('2026-09-27T15:14:42', $data[0]['created_at']);

        // Zuckerberg has 2 direct replies: Bezos and Jobs
        $aReplies = $data[0]['replies'];
        $this->assertCount(2, $aReplies);
        $this->assertSame('Mr. Bezos', $aReplies[0]['author']['name']);
        $this->assertSame('Mr. Jobs', $aReplies[1]['author']['name']);

        // Bezos has 1 reply: Musk
        $bReplies = $aReplies[0]['replies'];
        $this->assertCount(1, $bReplies);
        $this->assertSame('Mr. Musk', $bReplies[0]['author']['name']);

        // Musk has 1 reply: Gates
        $cReplies = $bReplies[0]['replies'];
        $this->assertCount(1, $cReplies);
        $this->assertSame('Mr. Gates', $cReplies[0]['author']['name']);

        // Gates has 0 replies
        $this->assertEmpty($cReplies[0]['replies']);

        // Jobs has 0 replies
        $this->assertEmpty($aReplies[1]['replies']);
    }

    public function test_authenticated_user_can_update_own_comment(): void
    {
        $user = User::factory()->create();
        $thread = Thread::factory()->create();
        $comment = Comment::factory()->create([
            'thread_id' => $thread->id,
            'user_id' => $user->id,
            'content' => 'Initial comment content',
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->putJson("/api/v1/comments/{$comment->id}", [
                'content' => 'Updated comment with refined clinical citations.',
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.content', 'Updated comment with refined clinical citations.');

        $this->assertSame('Updated comment with refined clinical citations.', $comment->fresh()->content);
    }

    public function test_user_cannot_update_others_comment(): void
    {
        $author = User::factory()->create();
        $stranger = User::factory()->create();
        $thread = Thread::factory()->create();
        $comment = Comment::factory()->create([
            'thread_id' => $thread->id,
            'user_id' => $author->id,
        ]);

        $response = $this->actingAs($stranger, 'sanctum')
            ->putJson("/api/v1/comments/{$comment->id}", [
                'content' => 'Unauthorized overwrite attempt',
            ]);

        $response->assertStatus(403);
    }
}
