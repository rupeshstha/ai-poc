<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\History>
 */
class HistoryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => \App\Models\User::first()?->id ?? \App\Models\User::factory()->create()->id,
            'event' => $this->faker->randomElement(['message', 'tool_use']),
            'content' => $this->faker->paragraph(),
        ];
    }
}
