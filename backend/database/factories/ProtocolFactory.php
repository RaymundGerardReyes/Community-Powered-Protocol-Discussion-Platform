<?php

namespace Database\Factories;

use App\Models\Protocol;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Protocol>
 */
class ProtocolFactory extends Factory
{
    protected $model = Protocol::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $title = fake()->sentence(3);

        return [
            'user_id' => User::factory(),
            'title' => $title,
            'slug' => Str::slug($title).'-'.fake()->unique()->numberBetween(100, 9999),
            'description' => fake()->paragraph(),
            'category' => fake()->randomElement(['DeFi', 'Governance', 'Security', 'Cryptography']),
            'version' => '1.0.0',
            'status' => 'published',
            'votes_count' => fake()->numberBetween(0, 50),
            'score' => fake()->numberBetween(0, 100),
            'reviews_count' => 0,
            'average_rating' => 0.00,
            'metadata' => ['tags' => ['consensus', 'l2']],
        ];
    }
}
