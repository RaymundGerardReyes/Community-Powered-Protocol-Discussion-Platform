<?php

namespace Database\Factories;

use App\Models\Protocol;
use App\Models\Thread;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Thread>
 */
class ThreadFactory extends Factory
{
    protected $model = Thread::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $title = fake()->sentence(4);

        return [
            'protocol_id' => Protocol::factory(),
            'user_id' => User::factory(),
            'title' => $title,
            'slug' => Str::slug($title).'-'.fake()->unique()->numberBetween(100, 9999),
            'content' => fake()->paragraphs(2, true),
            'is_pinned' => false,
            'views_count' => fake()->numberBetween(0, 100),
            'replies_count' => 0,
            'votes_count' => fake()->numberBetween(0, 20),
        ];
    }
}
