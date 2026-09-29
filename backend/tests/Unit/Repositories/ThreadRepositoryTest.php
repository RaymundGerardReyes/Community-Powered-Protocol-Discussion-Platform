<?php

namespace Tests\Unit\Repositories;

use App\Models\Comment;
use App\Models\Protocol;
use App\Models\Thread;
use App\Models\User;
use App\Repositories\Eloquent\ThreadRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class ThreadRepositoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_repository_strictly_aborts_with_503_when_typesense_is_unconfigured(): void
    {
        $repo = new ThreadRepository(null);

        $this->expectException(HttpException::class);
        $this->expectExceptionMessage('permanently removed');

        $repo->paginateForProtocol(1, [], 10);
    }

    public function test_paginate_for_protocol_queries_typesense_and_hydrates_thread_models(): void
    {
        $mockDocuments = $this->createMock(\Typesense\Documents::class);
        $mockDocuments->expects($this->once())
            ->method('search')
            ->with($this->callback(function (array $params) {
                return $params['q'] === '*'
                    && str_contains($params['filter_by'], 'protocol_id:=1')
                    && $params['per_page'] === 10;
            }))
            ->willReturn([
                'found' => 1,
                'hits' => [
                    [
                        'document' => [
                            'id' => '10',
                            'protocol_id' => 1,
                            'title' => 'Typesense Live Discussion',
                            'body' => 'Hydrated directly from Typesense without SQL',
                            'content' => 'Hydrated directly from Typesense without SQL',
                            'author' => 'Thread Validator',
                            'replies_count' => 0,
                            'votes_count' => 5,
                            'is_pinned' => false,
                            'is_locked' => false,
                            'created_at' => 1727654400,
                        ],
                    ],
                ],
            ]);

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

        $repo = new ThreadRepository($client);
        $paginator = $repo->paginateForProtocol(1, [], 10);

        $this->assertSame(1, $paginator->total());
        $this->assertSame('Typesense Live Discussion', $paginator->items()[0]->title);
        $this->assertSame('Thread Validator', $paginator->items()[0]->user->name);
    }

    public function test_find_by_id_with_replies_eager_loads_nested_comment_tree(): void
    {
        config(['scout.driver' => 'null']);

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

        $docData = [
            'id' => (string) $thread->id,
            'protocol_id' => $protocol->id,
            'title' => $thread->title,
            'content' => $thread->content,
            'author' => $user->name,
        ];

        $mockDocument = $this->createMock(\Typesense\Document::class);
        $mockDocument->expects($this->any())
            ->method('retrieve')
            ->willReturn($docData);

        $mockDocuments = $this->createMock(\Typesense\Documents::class);
        $mockDocuments->expects($this->any())
            ->method('offsetGet')
            ->with((string) $thread->id)
            ->willReturn($mockDocument);
        $mockDocuments->expects($this->any())
            ->method('search')
            ->willReturn([
                'found' => 1,
                'hits' => [['document' => $docData]],
            ]);

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

        $repo = new ThreadRepository($client);
        $loaded = $repo->findByIdWithReplies($thread->id);

        $this->assertSame($thread->id, $loaded->id);
        $this->assertCount(1, $loaded->comments);
        $this->assertSame('Root discussion point', $loaded->comments->first()->content);
        $this->assertCount(1, $loaded->comments->first()->replies);
        $this->assertSame('Nested reply level 1', $loaded->comments->first()->replies->first()->content);
        $this->assertCount(1, $loaded->comments->first()->replies->first()->replies);
        $this->assertSame('Deep reply level 2', $loaded->comments->first()->replies->first()->replies->first()->content);
    }
}
