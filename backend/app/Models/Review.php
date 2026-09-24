<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Review extends Model
{
    use HasFactory;

    protected $fillable = [
        'protocol_id',
        'user_id',
        'rating',
        'verdict',
        'summary',
        'findings',
    ];

    protected function casts(): array
    {
        return [
            'rating' => 'integer',
        ];
    }

    public function protocol(): BelongsTo
    {
        return $this->belongsTo(Protocol::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
