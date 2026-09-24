<?php

namespace Database\Factories;

use App\Models\Protocol;
use App\Models\Review;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Review>
 */
class ReviewFactory extends Factory
{
    protected $model = Review::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'protocol_id' => Protocol::factory(),
            'user_id' => User::factory(),
            'rating' => fake()->numberBetween(1, 5),
            'verdict' => fake()->randomElement(['approved', 'changes_requested', 'rejected']),
            'summary' => fake()->sentence(),
            'findings' => fake()->paragraph(),
        ];
    }
}
