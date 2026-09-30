<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreVoteRequest;
use App\Http\Resources\VoteResource;
use App\Services\VoteService;
use Illuminate\Http\JsonResponse;

class VoteController extends Controller
{
    public function __construct(
        protected VoteService $service
    ) {}

    /**
     * Cast or toggle a vote on a target entity.
     */
    public function store(StoreVoteRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $result = $this->service->cast(
            $request->user(),
            $validated['votable_type'],
            (int) $validated['votable_id'],
            (int) $validated['value']
        );

        return (new VoteResource($result))
            ->response()
            ->setStatusCode(200);
    }

    /**
     * Get dictionary of current user's votes across protocols, threads, and comments.
     */
    public function myVotes(\Illuminate\Http\Request $request): JsonResponse
    {
        $votes = $this->service->getUserVotes($request->user());

        return response()->json([
            'data' => $votes,
        ], 200);
    }
}
