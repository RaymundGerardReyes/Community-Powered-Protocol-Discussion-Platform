<?php

namespace App\Services;

use App\Models\Protocol;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * ProtocolService
 * Owns domain and business logic for Protocol.
 * Controllers must delegate here — no business rules inside Http/Controllers.
 */
class ProtocolService
{
    /**
     * Create a new Protocol record.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(User $user, array $data): Protocol
    {
        return DB::transaction(function () use ($user, $data) {
            $baseSlug = Str::slug($data['title']);
            $slug = $baseSlug;
            $counter = 1;

            while (Protocol::where('slug', $slug)->exists()) {
                $slug = "{$baseSlug}-{$counter}";
                $counter++;
            }

            $protocol = Protocol::create([
                'user_id' => $user->id,
                'title' => $data['title'],
                'slug' => $slug,
                'description' => $data['description'],
                'category' => $data['category'],
                'version' => $data['version'] ?? '1.0.0',
                'status' => $data['status'] ?? 'draft',
                'metadata' => $data['metadata'] ?? null,
            ]);

            Log::info('protocol.created', [
                'protocol_id' => $protocol->id,
                'user_id' => $user->id,
                'slug' => $protocol->slug,
            ]);

            return $protocol;
        });
    }

    /**
     * Update an existing Protocol record.
     *
     * @param  array<string, mixed>  $data
     *
     * @throws AuthorizationException
     */
    public function update(Protocol $protocol, User $user, array $data): Protocol
    {
        if ($protocol->user_id !== $user->id) {
            throw new AuthorizationException('You are not authorized to update this protocol.');
        }

        return DB::transaction(function () use ($protocol, $data) {
            if (isset($data['title']) && $data['title'] !== $protocol->title) {
                $baseSlug = Str::slug($data['title']);
                $slug = $baseSlug;
                $counter = 1;

                while (Protocol::where('slug', $slug)->where('id', '!=', $protocol->id)->exists()) {
                    $slug = "{$baseSlug}-{$counter}";
                    $counter++;
                }
                $data['slug'] = $slug;
            }

            $protocol->update($data);

            Log::info('protocol.updated', [
                'protocol_id' => $protocol->id,
                'updated_fields' => array_keys($data),
            ]);

            return $protocol;
        });
    }

    /**
     * Publish a protocol.
     *
     * @throws AuthorizationException
     */
    public function publish(Protocol $protocol, User $user): Protocol
    {
        if ($protocol->user_id !== $user->id) {
            throw new AuthorizationException('You are not authorized to publish this protocol.');
        }

        $protocol->update(['status' => 'published']);

        Log::info('protocol.published', [
            'protocol_id' => $protocol->id,
            'user_id' => $user->id,
        ]);

        return $protocol;
    }

    /**
     * Delete a protocol.
     *
     * @throws AuthorizationException
     */
    public function delete(Protocol $protocol, User $user): bool
    {
        if ($protocol->user_id !== $user->id) {
            throw new AuthorizationException('You are not authorized to delete this protocol.');
        }

        return DB::transaction(function () use ($protocol, $user) {
            $id = $protocol->id;
            $deleted = (bool) $protocol->delete();

            Log::info('protocol.deleted', [
                'protocol_id' => $id,
                'user_id' => $user->id,
            ]);

            return $deleted;
        });
    }
}
