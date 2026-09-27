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
     * Display a listing of comments for a thread assembled into an arbitrary depth tree.
     */
    public function index(Thread $thread): AnonymousResourceCollection
    {
        $allComments = $thread->comments()
            ->with('user')
            ->orderBy('created_at', 'asc')
            ->get();

        $tree = $this->buildCommentTree($allComments);

        return CommentResource::collection($tree);
    }

    /**
     * Reconstruct nested parent-child relationship tree in memory O(N).
     *
     * @param  \Illuminate\Support\Collection<int, Comment>  $comments
     * @return \Illuminate\Support\Collection<int, Comment>
     */
    protected function buildCommentTree(\Illuminate\Support\Collection $comments): \Illuminate\Support\Collection
    {
        $grouped = $comments->groupBy('parent_id');

        foreach ($comments as $comment) {
            $comment->setRelation('replies', $grouped->get($comment->id, collect()));
        }

        return $comments->whereNull('parent_id')->values();
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
