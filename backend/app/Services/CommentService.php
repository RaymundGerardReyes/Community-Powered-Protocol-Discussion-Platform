<?php

namespace App\Services;

use App\Models\Comment;

/**
 * CommentService
 * Owns domain and business logic for Comment.
 * Controllers must delegate here — no business rules inside Http/Controllers.
 */
class CommentService
{
    // TODO: implement create(), update(), delete(), and domain rules.
    // Example for VoteService: enforce one-vote-per-user-per-target,
    // execute inside DB::transaction(), then dispatch(new VoteCast($vote)).
}
