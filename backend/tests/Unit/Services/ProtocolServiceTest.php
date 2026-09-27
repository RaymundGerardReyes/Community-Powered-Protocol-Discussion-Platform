<?php

namespace Tests\Unit\Services;

use App\Models\Protocol;
use App\Models\User;
use App\Services\ProtocolService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProtocolServiceTest extends TestCase
{
    use RefreshDatabase;

    protected ProtocolService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(ProtocolService::class);
    }

    public function test_can_create_protocol_and_auto_generates_unique_slug(): void
    {
        $user = User::factory()->create();

        $protocol = $this->service->create($user, [
            'title' => 'Zero Knowledge Verifier Standard',
            'description' => 'Specification for ZK verifier modules.',
            'category' => 'Privacy',
            'version' => '1.0.0',
            'status' => 'draft',
        ]);

        $this->assertSame($user->id, $protocol->user_id);
        $this->assertSame('Zero Knowledge Verifier Standard', $protocol->title);
        $this->assertSame('zero-knowledge-verifier-standard', $protocol->slug);
        $this->assertSame('draft', $protocol->status);
        $this->assertSame('Privacy', $protocol->category);
        $this->assertDatabaseHas('protocols', ['id' => $protocol->id, 'slug' => 'zero-knowledge-verifier-standard']);
    }

    public function test_handles_slug_collisions_by_appending_incremental_counter(): void
    {
        $user = User::factory()->create();

        $protocol1 = $this->service->create($user, [
            'title' => 'Cross Chain Messaging',
            'description' => 'First spec.',
            'category' => 'Infrastructure',
        ]);

        $protocol2 = $this->service->create($user, [
            'title' => 'Cross Chain Messaging',
            'description' => 'Second spec.',
            'category' => 'Infrastructure',
        ]);

        $this->assertSame('cross-chain-messaging', $protocol1->slug);
        $this->assertSame('cross-chain-messaging-1', $protocol2->slug);
    }

    public function test_author_can_update_protocol_and_recomputes_slug_if_title_changes(): void
    {
        $user = User::factory()->create();
        $protocol = Protocol::factory()->create([
            'user_id' => $user->id,
            'title' => 'Initial Title',
            'slug' => 'initial-title',
        ]);

        $updated = $this->service->update($protocol, $user, [
            'title' => 'Revised Title Standard',
            'description' => 'Updated description content.',
        ]);

        $this->assertSame('Revised Title Standard', $updated->title);
        $this->assertSame('revised-title-standard', $updated->slug);
        $this->assertSame('Updated description content.', $updated->description);
    }

    public function test_non_author_cannot_update_protocol(): void
    {
        $this->expectException(AuthorizationException::class);

        $author = User::factory()->create();
        $otherUser = User::factory()->create();
        $protocol = Protocol::factory()->create(['user_id' => $author->id]);

        $this->service->update($protocol, $otherUser, [
            'title' => 'Malicious Modification',
        ]);
    }

    public function test_author_can_publish_protocol(): void
    {
        $user = User::factory()->create();
        $protocol = Protocol::factory()->create([
            'user_id' => $user->id,
            'status' => 'draft',
        ]);

        $published = $this->service->publish($protocol, $user);

        $this->assertSame('published', $published->status);
        $this->assertSame('published', $protocol->fresh()->status);
    }

    public function test_non_author_cannot_publish_protocol(): void
    {
        $this->expectException(AuthorizationException::class);

        $author = User::factory()->create();
        $otherUser = User::factory()->create();
        $protocol = Protocol::factory()->create([
            'user_id' => $author->id,
            'status' => 'draft',
        ]);

        $this->service->publish($protocol, $otherUser);
    }

    public function test_author_can_delete_protocol(): void
    {
        $user = User::factory()->create();
        $protocol = Protocol::factory()->create(['user_id' => $user->id]);

        $deleted = $this->service->delete($protocol, $user);

        $this->assertTrue($deleted);
        $this->assertDatabaseMissing('protocols', ['id' => $protocol->id]);
    }

    public function test_non_author_cannot_delete_protocol(): void
    {
        $this->expectException(AuthorizationException::class);

        $author = User::factory()->create();
        $otherUser = User::factory()->create();
        $protocol = Protocol::factory()->create(['user_id' => $author->id]);

        $this->service->delete($protocol, $otherUser);
    }
}
