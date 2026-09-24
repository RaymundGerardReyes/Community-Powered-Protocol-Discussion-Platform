<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCommentRequest;
use App\Http\Resources\CommentResource;
use App\Models\Comment;
use App\Models\Thread;
use App\Services\CommentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class CommentController extends Controller
{
    public function __construct(
        protected CommentService $service
    ) {}

    /**
     * Display a listing of comments for a thread.
     */
    public function index(Thread $thread): AnonymousResourceCollection
    {
        $comments = $thread->comments()
            ->whereNull('parent_id')
            ->with(['user', 'replies.user'])
            ->orderBy('created_at')
            ->get();

        return CommentResource::collection($comments);
    }

    /**
     * Store a newly created comment or nested reply on a thread.
     */
    public function store(StoreCommentRequest $request, Thread $thread): JsonResponse
    {
        $comment = $this->service->create($request->user(), $thread, $request->validated());

        return (new CommentResource($comment->load('user')))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Remove the specified comment.
     */
    public function destroy(Request $request, Comment $comment): JsonResponse
    {
        $this->service->delete($comment, $request->user());

        return response()->json([
            'message' => 'Comment deleted successfully',
        ]);
    }
}
