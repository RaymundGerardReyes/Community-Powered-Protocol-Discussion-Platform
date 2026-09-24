<?php

namespace Database\Factories;

use App\Models\Protocol;
use App\Models\User;
use App\Models\Vote;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Vote>
 */
class VoteFactory extends Factory
{
    protected $model = Vote::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'votable_type' => Protocol::class,
            'votable_id' => Protocol::factory(),
            'value' => 1,
        ];
    }
}
