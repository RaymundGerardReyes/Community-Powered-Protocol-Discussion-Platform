<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreThreadRequest;
use App\Http\Requests\UpdateThreadRequest;
use App\Http\Resources\ThreadResource;
use App\Models\Protocol;
use App\Models\Thread;
use App\Repositories\Contracts\ThreadRepositoryInterface;
use App\Services\ThreadService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ThreadController extends Controller
{
    public function __construct(
        protected ThreadRepositoryInterface $repository,
        protected ThreadService $service
    ) {}

    /**
     * Display a listing of threads for a protocol.
     */
    public function index(Request $request, int|string|Protocol $protocol): AnonymousResourceCollection
    {
        $protocolId = $protocol instanceof Protocol ? $protocol->id : (int) $protocol;
        $filters = $request->only(['sort', 'search']);
        $perPage = min((int) $request->input('per_page', 15), 100);

        $threads = $this->repository->paginateForProtocol($protocolId, $filters, $perPage);

        return ThreadResource::collection($threads);
    }

    /**
     * Display a single thread with its nested comment tree.
     */
    public function show(int $id): ThreadResource
    {
        $thread = $this->repository->findByIdWithReplies($id);
        $this->service->incrementViews($thread);

        return new ThreadResource($thread);
    }

    /**
     * Store a newly created thread in a protocol.
     */
    public function store(StoreThreadRequest $request, Protocol $protocol): JsonResponse
    {
        $thread = $this->service->create($request->user(), $protocol, $request->validated());

        return (new ThreadResource($thread->load('user')))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Update an existing thread.
     */
    public function update(UpdateThreadRequest $request, Thread $thread): ThreadResource
    {
        $updated = $this->service->update($thread, $request->user(), $request->validated());

        return new ThreadResource($updated->load('user'));
    }

    /**
     * Remove the specified thread.
     */
    public function destroy(Request $request, Thread $thread): JsonResponse
    {
        $this->service->delete($thread, $request->user());

        return response()->json([
            'message' => 'Thread deleted successfully',
        ]);
    }
}
