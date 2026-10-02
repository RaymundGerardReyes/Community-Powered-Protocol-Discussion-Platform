<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreProtocolRequest;
use App\Http\Requests\UpdateProtocolRequest;
use App\Http\Resources\ProtocolResource;
use App\Models\Protocol;
use App\Repositories\Contracts\ProtocolRepositoryInterface;
use App\Services\ProtocolService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ProtocolController extends Controller
{
    public function __construct(
        protected ProtocolRepositoryInterface $repository,
        protected ProtocolService $service
    ) {}

    /**
     * Display a listing of protocols with pagination, filters, and sorting.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $filters = $request->only(['status', 'category', 'search', 'sort']);
        $perPage = min((int) $request->input('per_page', 15), 100);

        $protocols = $this->repository->paginateWithFilters($filters, $perPage);

        return ProtocolResource::collection($protocols);
    }

    /**
     * Display a list of available protocol categories with counts.
     */
    public function categories(): JsonResponse
    {
        $categories = $this->repository->getCategories();

        return response()->json([
            'data' => $categories,
        ], 200);
    }

    /**
     * Display a specific protocol by slug.
     */
    public function show(string $slug): ProtocolResource
    {
        $protocol = $this->repository->findBySlugOrFail($slug);

        return new ProtocolResource($protocol);
    }

    /**
     * Store a newly created protocol.
     */
    public function store(StoreProtocolRequest $request): JsonResponse
    {
        $protocol = $this->service->create($request->user(), $request->validated());

        return (new ProtocolResource($protocol->load('user')))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Update an existing protocol.
     */
    public function update(UpdateProtocolRequest $request, Protocol $protocol): ProtocolResource
    {
        $updated = $this->service->update($protocol, $request->user(), $request->validated());

        return new ProtocolResource($updated->load('user'));
    }

    /**
     * Remove the specified protocol.
     */
    public function destroy(Request $request, Protocol $protocol): JsonResponse
    {
        $this->service->delete($protocol, $request->user());

        return response()->json([
            'message' => 'Protocol deleted successfully',
        ]);
    }
}
