<?php

namespace Tests\Unit\Repositories;

use App\Models\Comment;
use App\Models\Protocol;
use App\Models\Thread;
use App\Models\User;
use App\Repositories\Contracts\ThreadRepositoryInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ThreadRepositoryTest extends TestCase
{
    use RefreshDatabase;

    protected ThreadRepositoryInterface $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = app(ThreadRepositoryInterface::class);
    }

    public function test_paginate_for_protocol_filters_by_protocol_id(): void
    {
        $user = User::factory()->create();
        $protocol1 = Protocol::factory()->create(['user_id' => $user->id]);
        $protocol2 = Protocol::factory()->create(['user_id' => $user->id]);

        Thread::factory()->count(2)->create(['protocol_id' => $protocol1->id, 'user_id' => $user->id]);
        Thread::factory()->count(3)->create(['protocol_id' => $protocol2->id, 'user_id' => $user->id]);

        $paginator = $this->repository->paginateForProtocol($protocol1->id, [], 10);

        $this->assertSame(2, $paginator->total());
        foreach ($paginator->items() as $item) {
            $this->assertSame($protocol1->id, $item->protocol_id);
        }
    }

    public function test_find_by_id_with_replies_eager_loads_nested_comment_tree(): void
    {
        $user = User::factory()->create();
        $protocol = Protocol::factory()->create(['user_id' => $user->id]);
        $thread = Thread::factory()->create(['protocol_id' => $protocol->id, 'user_id' => $user->id]);

        $rootComment = Comment::factory()->create([
            'thread_id' => $thread->id,
            'user_id' => $user->id,
            'parent_id' => null,
            'content' => 'Root discussion point',
        ]);

        $replyComment = Comment::factory()->create([
            'thread_id' => $thread->id,
            'user_id' => $user->id,
            'parent_id' => $rootComment->id,
            'content' => 'Nested reply level 1',
        ]);

        Comment::factory()->create([
            'thread_id' => $thread->id,
            'user_id' => $user->id,
            'parent_id' => $replyComment->id,
            'content' => 'Deep reply level 2',
        ]);

        $loaded = $this->repository->findByIdWithReplies($thread->id);

        $this->assertSame($thread->id, $loaded->id);
        $this->assertCount(1, $loaded->comments);
        $this->assertSame('Root discussion point', $loaded->comments->first()->content);
        $this->assertCount(1, $loaded->comments->first()->replies);
        $this->assertSame('Nested reply level 1', $loaded->comments->first()->replies->first()->content);
        $this->assertCount(1, $loaded->comments->first()->replies->first()->replies);
        $this->assertSame('Deep reply level 2', $loaded->comments->first()->replies->first()->replies->first()->content);
    }
}
