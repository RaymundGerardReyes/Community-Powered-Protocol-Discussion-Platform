<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreReviewRequest;
use App\Http\Resources\ReviewResource;
use App\Models\Protocol;
use App\Models\Review;
use App\Services\ReviewService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ReviewController extends Controller
{
    public function __construct(
        protected ReviewService $service
    ) {}

    /**
     * Display a listing of reviews for a protocol.
     */
    public function index(Protocol $protocol): AnonymousResourceCollection
    {
        $reviews = $protocol->reviews()
            ->with('user')
            ->orderByDesc('created_at')
            ->paginate(15);

        return ReviewResource::collection($reviews);
    }

    /**
     * Store a newly created review for a protocol.
     */
    public function store(StoreReviewRequest $request, Protocol $protocol): JsonResponse
    {
        $review = $this->service->create($request->user(), $protocol, $request->validated());

        return (new ReviewResource($review->load('user')))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Update an existing review.
     */
    public function update(\App\Http\Requests\UpdateReviewRequest $request, Review $review): ReviewResource
    {
        $updated = $this->service->update($review, $request->user(), $request->validated());

        return new ReviewResource($updated->load('user'));
    }

    /**
     * Remove the specified review.
     */
    public function destroy(Request $request, Review $review): JsonResponse
    {
        $this->service->delete($review, $request->user());

        return response()->json([
            'message' => 'Review deleted successfully',
        ]);
    }
}
