<?php

namespace App\Services;

use App\Models\Vote;

/**
 * VoteService
 * Owns domain and business logic for Vote.
 * Controllers must delegate here — no business rules inside Http/Controllers.
 */
class VoteService
{
    // TODO: implement create(), update(), delete(), and domain rules.
    // Example for VoteService: enforce one-vote-per-user-per-target,
    // execute inside DB::transaction(), then dispatch(new VoteCast($vote)).
}
